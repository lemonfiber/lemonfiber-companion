<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneKindUpgraded;
use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Modules\Kernel\Api\WhatUpgradingItTakes;
use Modules\Kernel\Api\WhereTheAskingStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** One kind of media, with any of its words replaced where a test names it. */
function aKindToUpgrade(string $kind = 'movies', string $preset = 'Maximum'): OneKindUpgraded
{
    return OneKindUpgraded::reported($kind, $preset, WhatUpgradingItTakes::bytes(64_000_000_000), WhatBecameOfAskingIt::notAsked());
}

/** What upgrading a kind takes, as one line. */
function whatItTakes(WhatUpgradingItTakes $total): string
{
    return $total->either(
        bytes: static fn(int $bytes): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d bytes', $bytes)),
        unknown: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('unknown: %s', $why)),
    )->said;
}

it('keeps every word of a kind as it was reported, what it takes in total and what asking its service came to', function (): void {
    $asking = WhatBecameOfAskingIt::asked(WhereTheAskingStands::Started);
    $total = WhatUpgradingItTakes::unknown('Sonarr did not answer');
    $kind = OneKindUpgraded::reported('tv', 'Space-saving', $total, $asking);

    expect([$kind->kind(), $kind->preset(), $kind->total(), $kind->asking()])->toBe(['tv', 'Space-saving', $total, $asking]);
});

it('refuses a kind with any word left blank, naming the word', function (string $field): void {
    expect(static fn(): OneKindUpgraded => match ($field) {
        'media_type' => aKindToUpgrade(kind: ' '),
        default => aKindToUpgrade(preset: ''),
    })->toThrow(QualitySaysNothing::class, sprintf('`%s` blank', $field));
})->with(['media_type', 'preset']);

it('keeps what upgrading takes as bytes or as why it could not be worked out, and refuses a count below nothing or a blank reason', function (): void {
    expect(whatItTakes(WhatUpgradingItTakes::bytes(0)))->toBe('0 bytes')
        ->and(whatItTakes(WhatUpgradingItTakes::bytes(64_000_000_000)))->toBe('64000000000 bytes')
        ->and(whatItTakes(WhatUpgradingItTakes::unknown('Sonarr did not answer')))->toBe('unknown: Sonarr did not answer')
        ->and(static fn(): WhatUpgradingItTakes => WhatUpgradingItTakes::bytes(-1))->toThrow(QualitySaysNothing::class, '`total`')
        ->and(static fn(): WhatUpgradingItTakes => WhatUpgradingItTakes::unknown(' '))->toThrow(QualitySaysNothing::class, '`total`');
});

it('keeps the kinds in the stack\'s order, and says whether it was carried out', function (): void {
    $movies = aKindToUpgrade();
    $tv = aKindToUpgrade(kind: 'tv');
    $described = TheUpgrade::described(...['a' => $movies, 'b' => $tv]);
    $carriedOut = TheUpgrade::carriedOut(...['only' => $movies]);

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

    expect([$notAsked->saidOnTheScreen(), $notAsked->detail()])->toBe(['quality.asked.nothing-asked', ''])
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
        said: static fn(TheUpgrade $read): TheWordCarriedOut => new TheWordCarriedOut($read === $upgrade ? 'said' : 'another'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;

    expect($arm(WhatTheUpgradeCameTo::said($upgrade)))->toBe('said')
        ->and($arm(WhatTheUpgradeCameTo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('no_answer');
});
