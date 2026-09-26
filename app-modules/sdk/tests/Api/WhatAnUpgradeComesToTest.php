<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WhatAnUpgradeComesTo;
use Tests\Support\WhatTheContractAccepts;

/**
 * An `upgrade` envelope covering the kinds given, described.
 *
 * @param list<mixed> $media
 *
 * @return Envelope<mixed>
 */
function anUpgradeCovering(array $media): Envelope
{
    return new Envelope(1, 'upgrade', ['confirmed' => false, 'media' => $media]);
}

/**
 * One kind an upgrade covers, with its media type replaced.
 *
 * @return array<string, string>
 */
function aKindCovered(string $kind = 'movies'): array
{
    return ['media_type' => $kind, 'preset' => 'Maximum', 'size_per_hour' => '~15 GB'];
}

it('refuses a kind whose media type is only spaces, before anything is built from it', function (): void {
    expect(static fn(): mixed => WhatAnUpgradeComesTo::in(anUpgradeCovering([aKindCovered(' ')])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 0 of `media` in the upgrade envelope has no readable `media_type`');
});

it('refuses a kind whose media type is empty, before anything is built from it', function (): void {
    expect(static fn(): mixed => WhatAnUpgradeComesTo::in(anUpgradeCovering([aKindCovered('')])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 0 of `media`');
});

it('names the kind that could not be read by where it sits in the list', function (): void {
    expect(static fn(): mixed => WhatAnUpgradeComesTo::in(anUpgradeCovering([aKindCovered(), aKindCovered('tv'), 'anime'])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 2 of `media`')
        ->and(static fn(): mixed => WhatAnUpgradeComesTo::in(anUpgradeCovering([aKindCovered(), aKindCovered('tv'), aKindCovered(' ')])))
        ->toThrow(QualityIsUnreadable::class, 'Entry 2 of `media`');
});

it('spoils a payload the contract would accept in one place only', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UpgradeEnvelope', ['api_version' => 1, 'kind' => 'upgrade', 'data' => ['confirmed' => false, 'media' => [aKindCovered()]]]))->toBe([]);
});
