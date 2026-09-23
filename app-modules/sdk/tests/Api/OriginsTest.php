<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Sdk\Api\Origins;
use Modules\Sdk\Api\ProvenanceIsUnreadable;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `provenance` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see historySaying()}'s reason: what
 * is under test is what happens when the wire says something the contract does
 * not allow.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function provenanceSaying(array $data): Envelope
{
    return new Envelope(1, 'provenance', $data);
}

/**
 * One service with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aDeclaredService(string $id = 'sonarr'): array
{
    return [
        'id' => $id,
        'name' => 'Sonarr',
        'image' => 'lscr.io/linuxserver/sonarr',
        'pinned' => '4.0.15',
        'upstream' => 'https://github.com/Sonarr/Sonarr',
        'license' => 'GPL-3.0-only',
    ];
}

/**
 * Where these services come from.
 *
 * @param list<mixed> $services
 *
 * @return Envelope<mixed>
 */
function theOriginsOf(array $services): Envelope
{
    return provenanceSaying(['services' => $services]);
}

/**
 * Every origin a set holds, one line each, in its order.
 *
 * @return list<string>
 */
function everyOriginIn(WhereTheServicesComeFrom $origins): array
{
    $lines = [];

    foreach ($origins as $origin) {
        $lines[] = sprintf(
            '%s/%s/%s/%s/%s/%s',
            $origin->service()->named(),
            $origin->name(),
            $origin->image(),
            $origin->pinned(),
            $origin->upstream(),
            $origin->licence(),
        );
    }

    return $lines;
}

it('N11-R6, N11-R7 — reads every service with its image, pin, upstream and licence, in the stack\'s order', function (): void {
    $origins = Origins::in(theOriginsOf([aDeclaredService(), [...aDeclaredService('jellyfin'), 'name' => 'Jellyfin', 'license' => 'GPL-2.0-only']]));

    expect(everyOriginIn($origins))->toBe([
        'sonarr/Sonarr/lscr.io/linuxserver/sonarr/4.0.15/https://github.com/Sonarr/Sonarr/GPL-3.0-only',
        'jellyfin/Jellyfin/lscr.io/linuxserver/sonarr/4.0.15/https://github.com/Sonarr/Sonarr/GPL-2.0-only',
    ]);
});

it('a stack declaring no services is an answer', function (): void {
    expect(Origins::in(theOriginsOf([])))->toHaveCount(0);
});

it('refuses a payload that is not what the contract says', function (): void {
    expect(fn(): WhereTheServicesComeFrom => Origins::in(provenanceSaying([])))
        ->toThrow(ProvenanceIsUnreadable::class, '`services`')
        ->and(fn(): WhereTheServicesComeFrom => Origins::in(provenanceSaying(['services' => 'all of them'])))
        ->toThrow(ProvenanceIsUnreadable::class, '`services`');
});

it('refuses an envelope whose data is not a payload at all', function (): void {
    expect(fn(): WhereTheServicesComeFrom => Origins::in(new Envelope(1, 'provenance', 'nothing')))
        ->toThrow(ProvenanceIsUnreadable::class, '`data`');
});

it('refuses a row that is not a service, rather than dropping it', function (): void {
    // A list one row short says this stack does not run something it does.
    expect(fn(): WhereTheServicesComeFrom => Origins::in(theOriginsOf([aDeclaredService(), 'sonarr'])))
        ->toThrow(ProvenanceIsUnreadable::class, 'Service 1');
});

it('N11-R7 — refuses a service missing any word, naming which and where', function (string $field): void {
    $missing = aDeclaredService();
    unset($missing[$field]);

    expect(fn(): WhereTheServicesComeFrom => Origins::in(theOriginsOf([aDeclaredService('radarr'), $missing])))
        ->toThrow(ProvenanceIsUnreadable::class, sprintf('Service 1 in the provenance envelope has no readable `%s`', $field));
})->with(['id', 'name', 'image', 'pinned', 'upstream', 'license']);

it('N11-R7 — refuses a word that is blank or not text', function (mixed $said): void {
    expect(fn(): WhereTheServicesComeFrom => Origins::in(theOriginsOf([[...aDeclaredService(), 'license' => $said]])))
        ->toThrow(ProvenanceIsUnreadable::class, '`license`');
})->with([['  '], [null], [3]]);

it('refuses one service declared twice, by position', function (): void {
    // Here rather than left to the kernel, whose refusal the adapter would not
    // turn into an obstacle.
    expect(fn(): WhereTheServicesComeFrom => Origins::in(theOriginsOf([aDeclaredService(), aDeclaredService('radarr'), aDeclaredService()])))
        ->toThrow(ProvenanceIsUnreadable::class, 'Service 2 in the provenance envelope is `sonarr` again');
});

it('refuses one service declared twice under spellings the stack treats as one', function (): void {
    // The identity trims, so a check on the raw text would let the second
    // through to a kernel that refuses it as an uncaught raise.
    expect(fn(): WhereTheServicesComeFrom => Origins::in(theOriginsOf([aDeclaredService(), aDeclaredService(' sonarr ')])))
        ->toThrow(ProvenanceIsUnreadable::class, 'Service 1 in the provenance envelope is `sonarr` again');
});

it('judges the payload these cases are built on against the contract', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ProvenanceEnvelope', ['api_version' => 1, 'kind' => 'provenance', 'data' => ['services' => [aDeclaredService()]]]))
        ->toBe([]);
});
