<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnInputARecipeAsks;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARecipeRun;
use Modules\Kernel\Api\AStepThatRan;
use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheStepsThatRan;
use Modules\Kernel\Api\WhatARecipeRunDid;
use Modules\Kernel\Api\WhatAStepCameTo;

use function sprintf;

use Tests\Support\ARecipeRunAsItArrives;
use Tests\Support\TheWordCarriedOut;

it('refuses an input that will not say its name or what it asks, and keeps both trimmed', function (): void {
    $input = AnInputARecipeAsks::named(' node ', ' Which node ');

    expect([$input->name(), $input->ask()])->toBe(['node', 'Which node'])
        ->and(static fn(): AnInputARecipeAsks => AnInputARecipeAsks::named(' ', 'Which node'))->toThrow(PluginSaysNothing::class, '`name`')
        ->and(static fn(): AnInputARecipeAsks => AnInputARecipeAsks::named('node', ''))->toThrow(PluginSaysNothing::class, '`ask`');
});

it('refuses a step that ran with no id or nowhere it called, and says what it came to', function (): void {
    $step = AStepThatRan::reported(' nudge ', ' plex.tv ', WhatAStepCameTo::Withheld, ' Nobody approved the token ');

    expect([$step->step(), $step->to(), $step->came(), $step->why()])->toBe(['nudge', 'plex.tv', WhatAStepCameTo::Withheld, 'Nobody approved the token'])
        ->and($step->came()->saidOnTheScreen())->toBe('plugins.run.came.withheld')
        ->and(static fn(): AStepThatRan => AStepThatRan::reported('', 'plex.tv', WhatAStepCameTo::Answered, ''))->toThrow(PluginSaysNothing::class, '`step`')
        ->and(static fn(): AStepThatRan => AStepThatRan::reported('nudge', ' ', WhatAStepCameTo::Answered, ''))->toThrow(PluginSaysNothing::class, '`to`');
});

it('is a reading until it ran, and takes the arm it was made on', function (): void {
    $said = static fn(ARecipeRun $run): string => $run->either(
        rehearsed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('rehearsed'),
        ran: static fn(WhatARecipeRunDid $did): TheWordCarriedOut => new TheWordCarriedOut(sprintf('ran, %s, %d steps', $did->held() ? 'held' : 'not held', $did->steps()->count())),
    )->said;
    $rehearsed = ARecipeRun::rehearsed(APluginId::called('tdarr'), ARecipeRunAsItArrives::theRecipe());
    $ran = ARecipeRun::ran(APluginId::called('tdarr'), ARecipeRunAsItArrives::theRecipe(), WhatARecipeRunDid::reported(held: false, why: ' Plex would not answer ', steps: TheStepsThatRan::these(...['a' => AStepThatRan::reported('nudge', 'plex.tv', WhatAStepCameTo::Unreachable, '')])));

    expect([$rehearsed->isAReading(), $said($rehearsed), $rehearsed->plugin()->named(), $rehearsed->recipe()->id()])->toBe([true, 'rehearsed', 'tdarr', 'resync'])
        ->and([$ran->isAReading(), $said($ran)])->toBe([false, 'ran, not held, 1 steps']);
});

it('names running a recipe as the action the stack takes', function (): void {
    expect(ExtendingIt::Run->asked())->toBe('plugin-run');
});

it('reads a run of a recipe of a plugin only as that recipe of that plugin, and only while it is a reading', function (): void {
    $plugin = ARecipeRunAsItArrives::thePlugin();

    expect(ARecipeRunAsItArrives::theAnswer(ran: false)->readsARunOf($plugin, ARecipeRunAsItArrives::theRecipe()))->toBeTrue()
        ->and(ARecipeRunAsItArrives::theAnswer(ran: false)->readsARunOf($plugin, ARecipeRunAsItArrives::theAskingRecipe()))->toBeFalse()
        ->and(ARecipeRunAsItArrives::theAnswer(ran: true)->readsARunOf($plugin, ARecipeRunAsItArrives::theRecipe()))->toBeFalse()
        ->and(ARecipeRunAsItArrives::theAnswer(ran: false)->agreement())->not->toBe('')
        ->and(ARecipeRunAsItArrives::theAnswer(ran: true)->agreement())->toBe('');
});
