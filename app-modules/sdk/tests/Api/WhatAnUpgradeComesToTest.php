<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WhatAnUpgradeComesTo;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
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
 * @return array<string, mixed>
 */
function aKindCovered(string $kind = 'movies', mixed $total = ['bytes' => 64_000_000_000]): array
{
    return ['media_type' => $kind, 'preset' => 'Maximum', 'total' => $total];
}

/** What upgrading the first kind takes, as one line. */
function theTotalRead(mixed $total): string
{
    foreach (WhatAnUpgradeComesTo::in(anUpgradeCovering([aKindCovered(total: $total)])) as $kind) {
        return $kind->total()->either(
            bytes: static fn(int $bytes): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d bytes', $bytes)),
            unknown: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('unknown: %s', $why)),
        )->said;
    }

    return '';
}

it('reads what upgrading a kind takes in total, and why where the stack could not work it out', function (): void {
    expect(theTotalRead(['bytes' => 64_000_000_000]))->toBe('64000000000 bytes')
        ->and(theTotalRead(['bytes' => 0]))->toBe('0 bytes')
        ->and(theTotalRead(['unknown' => 'Radarr did not answer']))->toBe('unknown: Radarr did not answer');
});

it('refuses a total that is neither one count of bytes nor one reason', function (mixed $total): void {
    expect(static fn(): string => theTotalRead($total))
        ->toThrow(QualityIsUnreadable::class, 'Entry 0 of `media` in the upgrade envelope has no readable `total`');
})->with([
    'none' => [null],
    'a word' => ['64 GB'],
    'nothing in it' => [[]],
    'both arms' => [['bytes' => 1, 'unknown' => 'Both']],
    'bytes below nothing' => [['bytes' => -1]],
    'bytes as text' => [['bytes' => '64000000000']],
    'a blank reason' => [['unknown' => ' ']],
    'another arm' => [['rate' => '~15 GB']],
]);

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
    expect(WhatTheContractAccepts::complaintsAbout('UpgradeEnvelope', ['api_version' => 1, 'kind' => 'upgrade', 'data' => ['confirmed' => false, 'media' => [aKindCovered()], 'rehearsed' => false]]))->toBe([]);
});
