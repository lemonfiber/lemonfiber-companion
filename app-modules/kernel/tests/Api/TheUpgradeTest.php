<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneKindUpgraded;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Modules\Kernel\Api\WhereTheAskingStands;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheUpgradeTook
{
    public function __construct(public string $said) {}
}

/** One kind of media, with any of its words replaced where a test names it. */
function aKindToUpgrade(string $kind = 'movies', string $preset = 'Maximum', string $size = '~15 GB'): OneKindUpgraded
{
    return OneKindUpgraded::reported($kind, $preset, $size, WhatBecameOfAskingIt::notAsked());
}

it('keeps every word of a kind as it was reported, and what asking its service came to', function (): void {
    $asking = WhatBecameOfAskingIt::asked(WhereTheAskingStands::Started);
    $kind = OneKindUpgraded::reported('tv', 'Space-saving', '~1 GB', $asking);

    expect([$kind->kind(), $kind->preset(), $kind->sizePerHour(), $kind->asking()])->toBe(['tv', 'Space-saving', '~1 GB', $asking]);
});

it('refuses a kind with any word left blank, naming the word', function (string $field): void {
    expect(static fn(): OneKindUpgraded => match ($field) {
        'media_type' => aKindToUpgrade(kind: ' '),
        'preset' => aKindToUpgrade(preset: ''),
        default => aKindToUpgrade(size: ' '),
    })->toThrow(QualitySaysNothing::class, sprintf('`%s` blank', $field));
})->with(['media_type', 'preset', 'size_per_hour']);

it('keeps the kinds in the stack\'s order, and says whether it was carried out', function (): void {
    $movies = aKindToUpgrade();
    $tv = aKindToUpgrade(kind: 'tv');
    $described = TheUpgrade::described(...['a' => $movies, 'b' => $tv]);
    $carriedOut = TheUpgrade::carriedOut($movies);

    expect([iterator_to_array($described, preserve_keys: true), $described->wasCarriedOut()])->toBe([[$movies, $tv], false])
        ->and($described)->toHaveCount(2)
        ->and([iterator_to_array($carriedOut, preserve_keys: true), $carriedOut->wasCarriedOut()])->toBe([[$movies], true])
        ->and($carriedOut)->toHaveCount(1);
});

it('says what became of asking a service, or that nothing was asked', function (): void {
    $notAsked = WhatBecameOfAskingIt::notAsked();
    $started = WhatBecameOfAskingIt::asked(WhereTheAskingStands::Started);
    $notReady = WhatBecameOfAskingIt::asked(WhereTheAskingStands::NotStarted);
    $failed = WhatBecameOfAskingIt::failed('Sonarr said no');

    expect([$notAsked->saidOnTheScreen(), $notAsked->detail()])->toBe(['quality.asked.not-asked', ''])
        ->and([$started->saidOnTheScreen(), $started->detail()])->toBe(['quality.asked.started', ''])
        ->and([$notReady->saidOnTheScreen()])->toBe(['quality.asked.not-started'])
        ->and([$failed->saidOnTheScreen(), $failed->detail()])->toBe(['quality.asked.failed', 'Sonarr said no']);
});

it('refuses a failure that does not say why, by either road', function (): void {
    expect(static fn(): WhatBecameOfAskingIt => WhatBecameOfAskingIt::failed(' '))->toThrow(QualitySaysNothing::class, '`detail` blank')
        ->and(static fn(): WhatBecameOfAskingIt => WhatBecameOfAskingIt::asked(WhereTheAskingStands::Failed))->toThrow(QualitySaysNothing::class, '`detail` blank');
});

it('makes a yes to an upgrade only out of one that was described', function (): void {
    expect(AnUpgradeDescribed::by(TheUpgrade::described(aKindToUpgrade())))->toBeInstanceOf(AnUpgradeDescribed::class)
        ->and(static fn(): AnUpgradeDescribed => AnUpgradeDescribed::by(TheUpgrade::carriedOut()))
        ->toThrow(ThereIsNothingToAgreeTo::class, 'agreed to against one already carried out');
});

it('tells an upgrade from what stood in the way of it', function (): void {
    $upgrade = TheUpgrade::described();
    $arm = static fn(WhatTheUpgradeCameTo $came): string => $came->either(
        said: static fn(TheUpgrade $read): WhichArmTheUpgradeTook => new WhichArmTheUpgradeTook($read === $upgrade ? 'said' : 'another'),
        met: static fn(Obstacle $why): WhichArmTheUpgradeTook => new WhichArmTheUpgradeTook($why->value),
    )->said;

    expect($arm(WhatTheUpgradeCameTo::said($upgrade)))->toBe('said')
        ->and($arm(WhatTheUpgradeCameTo::met(Obstacle::StackDidNotAnswer)))->toBe('no_answer');
});
