<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AdviceSaysNothing;
use Modules\Kernel\Api\APossibleCause;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SomethingThatGoesWrong;
use Modules\Kernel\Api\TheDevices;
use Modules\Kernel\Api\TheTroubles;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Kernel\Api\WhatWasFoundToWatchOn;
use Modules\Kernel\Api\WhyPlaybackMayStruggle;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheAdviceTook
{
    public function __construct(public string $said) {}
}

/** Advice with the two sentences given here and nothing listed. */
function adviceSaying(string $onlyAtHome, string $nothingIsInstalled): WhatToWatchOn
{
    return WhatToWatchOn::advised(TheDevices::of(), $onlyAtHome, $nothingIsInstalled, WhyPlaybackMayStruggle::nothing(), TheTroubles::of());
}

it('keeps everything it was advised with', function (): void {
    $devices = TheDevices::of();
    $straining = WhyPlaybackMayStruggle::said('Archival', 'No 4K here', 'Choose Balanced');
    $troubles = TheTroubles::of();
    $advice = WhatToWatchOn::advised($devices, 'At home only', 'Nothing is installed', $straining, $troubles);

    expect([$advice->devices(), $advice->onlyAtHome(), $advice->nothingIsInstalled(), $advice->straining(), $advice->troubles()])
        ->toBe([$devices, 'At home only', 'Nothing is installed', $straining, $troubles]);
});

it('refuses advice that will not say where it works or what it will not do', function (): void {
    expect(fn(): WhatToWatchOn => adviceSaying(' ', 'Nothing is installed'))->toThrow(AdviceSaysNothing::class, '`only_at_home`')
        ->and(fn(): WhatToWatchOn => adviceSaying('At home only', ''))->toThrow(AdviceSaysNothing::class, '`nothing_is_installed`');
});

it('keeps what strains playback, and nothing where nothing does', function (): void {
    $straining = WhyPlaybackMayStruggle::said('Archival', 'No 4K here', 'Choose Balanced');

    expect([$straining->preset(), $straining->caution(), $straining->instead()])->toBe(['Archival', 'No 4K here', 'Choose Balanced'])
        ->and([WhyPlaybackMayStruggle::nothing()->preset(), WhyPlaybackMayStruggle::nothing()->caution(), WhyPlaybackMayStruggle::nothing()->instead()])->toBe(['', '', '']);
});

it('refuses a strain that does not say one of its words', function (): void {
    expect(fn(): WhyPlaybackMayStruggle => WhyPlaybackMayStruggle::said(' ', 'No 4K here', 'Choose Balanced'))->toThrow(AdviceSaysNothing::class, '`preset`')
        ->and(fn(): WhyPlaybackMayStruggle => WhyPlaybackMayStruggle::said('Archival', '', 'Choose Balanced'))->toThrow(AdviceSaysNothing::class, '`caution`')
        ->and(fn(): WhyPlaybackMayStruggle => WhyPlaybackMayStruggle::said('Archival', 'No 4K here', ' '))->toThrow(AdviceSaysNothing::class, '`instead`');
});

it('keeps a cause\'s three words, and refuses one that is blank', function (): void {
    $cause = APossibleCause::said('Weak Wi-Fi', 'Only far away', 'Move closer');

    expect([$cause->because(), $cause->tell(), $cause->fix()])->toBe(['Weak Wi-Fi', 'Only far away', 'Move closer'])
        ->and(fn(): APossibleCause => APossibleCause::said(' ', 'Only far away', 'Move closer'))->toThrow(AdviceSaysNothing::class, '`because`')
        ->and(fn(): APossibleCause => APossibleCause::said('Weak Wi-Fi', '', 'Move closer'))->toThrow(AdviceSaysNothing::class, '`tell`')
        ->and(fn(): APossibleCause => APossibleCause::said('Weak Wi-Fi', 'Only far away', ' '))->toThrow(AdviceSaysNothing::class, '`fix`');
});

it('keeps a symptom and its causes, most likely first, and refuses a blank symptom', function (): void {
    $trouble = SomethingThatGoesWrong::said('It buffers', ...['first' => APossibleCause::said('Weak Wi-Fi', 'Far away', 'Move'), 'second' => APossibleCause::said('Busy line', 'Evenings', 'Wait')]);
    $causes = [];

    foreach ($trouble as $cause) {
        $causes[] = $cause->because();
    }

    expect($trouble->symptom())->toBe('It buffers')
        ->and($causes)->toBe(['Weak Wi-Fi', 'Busy line'])
        ->and(array_keys(iterator_to_array($trouble, preserve_keys: true)))->toBe([0, 1])
        ->and($trouble)->toHaveCount(2)
        ->and(fn(): SomethingThatGoesWrong => SomethingThatGoesWrong::said(' '))->toThrow(AdviceSaysNothing::class, '`symptom`');
});

it('keeps every symptom in the stack\'s order', function (): void {
    $troubles = TheTroubles::of(...['first' => SomethingThatGoesWrong::said('It buffers'), 'second' => SomethingThatGoesWrong::said('No sound')]);
    $symptoms = [];

    foreach ($troubles as $trouble) {
        $symptoms[] = $trouble->symptom();
    }

    expect($symptoms)->toBe(['It buffers', 'No sound'])
        ->and(array_keys(iterator_to_array($troubles, preserve_keys: true)))->toBe([0, 1])
        ->and($troubles)->toHaveCount(2);
});

it('advice that could not be asked for is never advice with nothing in it', function (): void {
    $fold = static fn(WhatWasFoundToWatchOn $answer): string => $answer->either(
        found: static fn(WhatToWatchOn $advice): WhichArmTheAdviceTook => new WhichArmTheAdviceTook(sprintf('found:%s', $advice->onlyAtHome())),
        met: static fn(Obstacle $why): WhichArmTheAdviceTook => new WhichArmTheAdviceTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundToWatchOn::found(adviceSaying('At home only', 'Nothing is installed'))))->toBe('found:At home only')
        ->and($fold(WhatWasFoundToWatchOn::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
