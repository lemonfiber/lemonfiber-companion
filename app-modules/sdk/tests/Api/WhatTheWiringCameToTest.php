<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_slice;
use function count;
use function expect;
use function explode;
use function implode;
use function is_array;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\TheWiring;
use Modules\Sdk\Api\SeedIsUnreadable;
use Modules\Sdk\Api\WhatTheWiringCameTo;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `seed` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function aRunSaying(mixed $data, int $version = 1): Envelope
{
    return new Envelope($version, 'seed', $data);
}

/**
 * A run with one connection in every state, and a warning, so a refusal can be asked to name any of them.
 *
 * @return array<string, mixed>
 */
function aRunOfEveryState(): array
{
    $informational = ['severity' => 'informational'];

    return [
        'assessment' => 'assessed',
        'rehearsed' => false,
        'unsupported' => [['what' => 'tautulli', 'because' => 'lemonfiber cannot speak to it'], ['what' => 'bazarr', 'because' => 'Nor to this one']],
        'wirings' => [
            ['connection' => 'SABnzbd into Sonarr', 'severity' => $informational, 'state' => ['state' => 'wired']],
            ['connection' => 'SABnzbd into Radarr', 'severity' => ['severity' => 'warning', 'breakage' => 'Downloads never arrive', 'remediation' => 'Point it again'], 'state' => ['state' => 'conflicted', 'ours' => '/downloads', 'yours' => '/mnt/downloads']],
            ['connection' => 'Prowlarr into Sonarr', 'severity' => $informational, 'state' => ['state' => 'would-wire', 'ours' => '8989', 'yours' => '8990']],
            ['connection' => 'Prowlarr into Radarr', 'severity' => $informational, 'state' => ['state' => 'skipped', 'reason' => 'Radarr was not up yet']],
            ['connection' => 'Jellyfin into Jellyseerr', 'severity' => $informational, 'state' => ['state' => 'failed', 'detail' => '401 Unauthorized']],
            ['connection' => 'qBittorrent into Sonarr', 'severity' => $informational, 'state' => ['state' => 'already-wired']],
            ['connection' => 'qBittorrent into Radarr', 'severity' => $informational, 'state' => ['state' => 'drifted']],
            ['connection' => 'Bazarr into Sonarr', 'severity' => $informational, 'state' => ['state' => 'stale']],
            ['connection' => 'Bazarr into Radarr', 'severity' => $informational, 'state' => ['state' => 'adopted']],
            ['connection' => 'Lidarr into SABnzbd', 'severity' => $informational, 'state' => ['state' => 'unmanaged']],
            ['connection' => 'Lidarr into Prowlarr', 'severity' => $informational, 'state' => ['state' => 'would-adopt']],
            ['connection' => 'Readarr into SABnzbd', 'severity' => $informational, 'state' => ['state' => 'observed', 'reason' => 'You declared it unmanaged']],
            ['connection' => 'Readarr into Prowlarr', 'severity' => $informational, 'state' => ['state' => 'refused', 'reason' => 'Two arrs share one root folder']],
        ],
    ];
}

/**
 * That run with one value replaced, or taken away where the value is `absent`.
 *
 * @param array<mixed>     $data
 * @param list<int|string> $path
 * @return array<mixed>
 */
function aRunWith(array $data, array $path, mixed $value): array
{
    $key = $path[0];

    if (count($path) > 1) {
        $inside = $data[$key];
        $data[$key] = aRunWith(is_array($inside) ? $inside : [], array_slice($path, 1), $value);

        return $data;
    }

    if ($value === 'absent') {
        unset($data[$key]);

        return $data;
    }

    $data[$key] = $value;

    return $data;
}

/**
 * Every connection, a line each, with everything it carries.
 *
 * @return list<string>
 */
function everyConnectionRead(TheWiring $wiring): array
{
    $lines = [sprintf('%s|%s', $wiring->judged()->value, $wiring->wasRehearsed() ? 'rehearsed' : 'written')];

    foreach ($wiring->unsupported() as $limit) {
        $lines[] = sprintf('cannot %s: %s', $limit->what(), $limit->because());
    }

    foreach ($wiring as $connection) {
        $ended = $connection->ended();
        $lines[] = sprintf('%s|%s|%s|%s|%s|%s|%s', $connection->connection(), $ended->state()->value, $ended->said(), $ended->ours(), $ended->yours(), $connection->breaks()->breakage(), $connection->breaks()->remediation());
    }

    return $lines;
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SeedEnvelope', ['api_version' => 1, 'kind' => 'seed', 'data' => aRunOfEveryState()]))->toBe([]);
});

it('reads every connection in its own state, with the stack\'s words and what a warning breaks', function (): void {
    expect(implode("\n", everyConnectionRead(WhatTheWiringCameTo::in(aRunSaying(aRunOfEveryState())))))->toBe(implode("\n", [
        'assessed|written',
        'cannot tautulli: lemonfiber cannot speak to it',
        'cannot bazarr: Nor to this one',
        'SABnzbd into Sonarr|wired|||||',
        'SABnzbd into Radarr|conflicted||/downloads|/mnt/downloads|Downloads never arrive|Point it again',
        'Prowlarr into Sonarr|would-wire||8989|8990||',
        'Prowlarr into Radarr|skipped|Radarr was not up yet||||',
        'Jellyfin into Jellyseerr|failed|401 Unauthorized||||',
        'qBittorrent into Sonarr|already-wired|||||',
        'qBittorrent into Radarr|drifted|||||',
        'Bazarr into Sonarr|stale|||||',
        'Bazarr into Radarr|adopted|||||',
        'Lidarr into SABnzbd|unmanaged|||||',
        'Lidarr into Prowlarr|would-adopt|||||',
        'Readarr into SABnzbd|observed|You declared it unmanaged||||',
        'Readarr into Prowlarr|refused|Two arrs share one root folder||||',
    ]));
});

it('reads a rehearsed run as one, and a run that could not judge drift as that', function (): void {
    $run = WhatTheWiringCameTo::in(aRunSaying([...aRunOfEveryState(), 'rehearsed' => true, 'assessment' => 'unassessable']));

    expect(everyConnectionRead($run)[0])->toBe('unassessable|rehearsed');
});

it('reads a run that lists nothing it cannot wire as none', function (): void {
    expect(WhatTheWiringCameTo::in(aRunSaying(aRunWith(aRunOfEveryState(), ['unsupported'], 'absent')))->unsupported())->toHaveCount(0);
});

it('reads what the stack may leave out of a conflict or a rehearsed wiring as nothing, where it is absent or null', function (int $at, string $field, mixed $said, string $read): void {
    expect(everyConnectionRead(WhatTheWiringCameTo::in(aRunSaying(aRunWith(aRunOfEveryState(), ['wirings', $at, 'state', $field], $said))))[$at + 3])->toBe($read);
})->with([
    [1, 'yours', 'absent', 'SABnzbd into Radarr|conflicted||/downloads||Downloads never arrive|Point it again'],
    [1, 'yours', null, 'SABnzbd into Radarr|conflicted||/downloads||Downloads never arrive|Point it again'],
    [2, 'ours', 'absent', 'Prowlarr into Sonarr|would-wire|||8990||'],
    [2, 'yours', null, 'Prowlarr into Sonarr|would-wire||8989|||'],
]);

it('refuses a payload with no data', function (): void {
    expect(fn(): TheWiring => WhatTheWiringCameTo::in(aRunSaying('nothing')))->toThrow(SeedIsUnreadable::class, 'no readable `data`');
});

it('refuses a field of the run itself that is missing or not what the contract says', function (string $field, mixed $said): void {
    expect(fn(): TheWiring => WhatTheWiringCameTo::in(aRunSaying(aRunWith(aRunOfEveryState(), [$field], $said))))
        ->toThrow(SeedIsUnreadable::class, sprintf('The seed envelope has no readable `%s`', $field));
})->with([
    ['assessment', 'absent'], ['assessment', 'judged'], ['assessment', ' '], ['rehearsed', 'no'], ['rehearsed', 'absent'],
    ['wirings', 'absent'], ['wirings', 'none'], ['unsupported', null],
]);

it('refuses a connection that does not say what it owes, naming the second place it could be', function (int $at, string $inside, mixed $said, string $field): void {
    $path = $inside === '' ? ['wirings', $at] : ['wirings', $at, ...explode('.', $inside)];

    expect(fn(): TheWiring => WhatTheWiringCameTo::in(aRunSaying(aRunWith(aRunOfEveryState(), $path, $said))))
        ->toThrow(SeedIsUnreadable::class, sprintf('Entry %d of `wirings` in the seed envelope has no readable `%s`', $at, $field));
})->with([
    [1, '', 'spoiled', 'connection'],
    [1, 'connection', ' ', 'connection'],
    [1, 'severity', 'warning', 'severity'],
    [1, 'severity.severity', 'grave', 'severity'],
    [1, 'severity.severity', 'absent', 'severity'],
    [1, 'severity.breakage', ' ', 'breakage'],
    [1, 'severity.remediation', 'absent', 'remediation'],
    [1, 'state', 'wired', 'state'],
    [1, 'state.state', 'mostly', 'state'],
    [1, 'state.state', 7, 'state'],
    [1, 'state.ours', 'absent', 'ours'],
    [1, 'state.yours', ' ', 'yours'],
    [2, 'state.ours', 7, 'ours'],
    [3, 'state.reason', '', 'reason'],
    [4, 'state.detail', 'absent', 'detail'],
    [11, 'state.reason', 'absent', 'reason'],
    [12, 'state.reason', ' ', 'reason'],
]);

it('refuses what the run cannot wire where it does not say what or why, naming the second', function (mixed $said, string $field): void {
    $path = $field === 'the row' ? ['unsupported', 1] : ['unsupported', 1, $field];

    expect(fn(): TheWiring => WhatTheWiringCameTo::in(aRunSaying(aRunWith(aRunOfEveryState(), $path, $said))))
        ->toThrow(SeedIsUnreadable::class, sprintf('Entry 1 of `unsupported` in the seed envelope has no readable `%s`', $field === 'the row' ? 'what' : $field));
})->with([['spoiled', 'the row'], [' ', 'what'], ['absent', 'because']]);

it('refuses an envelope in a version this app does not read', function (): void {
    expect(fn(): TheWiring => WhatTheWiringCameTo::in(aRunSaying(aRunOfEveryState(), version: 99)))->toThrow(EnvelopeIsNotRead::class);
});
