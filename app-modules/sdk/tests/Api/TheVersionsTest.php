<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\VersionIsBlank;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Sdk\Api\ChangelogIsUnreadable;
use Modules\Sdk\Api\TheVersions;
use Modules\Sdk\Api\VersionsAreUnreadable;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * What a `version` envelope this side cannot read does to the reader.
 *
 * Built by hand rather than fetched, for `StandingsTest`'s reason: what is
 * tested here is what the reading does with a payload the contract does not
 * allow. The payload a stack really sends is asserted in
 * `tests/Contract/ReadingVersionsContractTest.php`.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function versionsSaying(array $data): Envelope
{
    return new Envelope(1, 'version', $data);
}

/**
 * What a stack sends, with whatever this case is about changed.
 *
 * @param array<string, mixed> $changed
 * @param array<string, mixed> $changelog
 *
 * @return array<string, mixed>
 */
function aVersionPayload(array $changed = [], array $changelog = []): array
{
    return [
        'binary' => '0.16.0',
        'supported_schema' => [1],
        'stack' => '0.9.0',
        'compose' => 'Docker Compose version v2.29.1',
        'changelog' => [
            'state' => 'current',
            'running' => [
                'version' => '0.16.0',
                'tag' => 'v0.16.0',
                'user_facing' => false,
                'groups' => [['title' => 'Fixed', 'entries' => [['summary' => 'A stuck download is said once', 'requirements' => []]]]],
            ],
            'releases' => [],
            'requirements' => [],
            ...$changelog,
        ],
        ...$changed,
    ];
}

/** The groups a reading holds, as `title: entry, entry`. */
function theGroupsRead(WhatRunsHere $runs): string
{
    return $runs->running(
        named: static fn(Release $release, array $changes): AsRead => new AsRead(implode(' / ', array_map(
            static fn(AGroupOfChanges $group): string => sprintf('%s: %s', $group->title(), implode(', ', iterator_to_array($group, preserve_keys: false))),
            $changes,
        ))),
        notNamed: static fn(): AsRead => new AsRead('-'),
    )->said;
}

/** One line carried out of an arm. */
final readonly class AsRead
{
    public function __construct(public string $said) {}
}

it('reads an engine the stack could not ask as none, rather than refusing the rest', function (): void {
    $absent = TheVersions::in(versionsSaying(aVersionPayload(['compose' => null])));

    expect($absent->engine())->toBe('')
        ->and($absent->lemonfiber())->toBe('0.16.0')
        ->and($absent->stack())->toBe('0.9.0');
});

it('reads a stack naming no running release as one, with no notes', function (): void {
    $runs = TheVersions::in(versionsSaying(aVersionPayload(changelog: ['state' => 'pending', 'running' => null])));

    expect($runs->notes())->toBe(HowTheNotesStand::Pending)
        ->and(theGroupsRead($runs))->toBe('-');
});

it('reads the running release\'s notes group by group', function (): void {
    expect(theGroupsRead(TheVersions::in(versionsSaying(aVersionPayload()))))->toBe('Fixed: A stuck download is said once');
});

it('refuses a payload short of a version it always carries', function (string $field): void {
    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload([$field => null]))))
        ->toThrow(VersionsAreUnreadable::class, sprintf('`%s`', $field));
})->with(['binary', 'stack']);

it('refuses a blank version rather than drawing one', function (): void {
    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload(['binary' => ' ']))))
        ->toThrow(VersionIsBlank::class);
});

it('refuses notes whose standing is missing or not a word the contract has', function (): void {
    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload(changelog: ['state' => null]))))
        ->toThrow(VersionsAreUnreadable::class, '`state`')
        ->and(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload(changelog: ['state' => 'fresh']))))
        ->toThrow(VersionsAreUnreadable::class, '`fresh`');
});

it('refuses a payload with no changelog at all', function (): void {
    $payload = aVersionPayload();
    unset($payload['changelog']);

    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying($payload)))->toThrow(ChangelogIsUnreadable::class);
});

it('refuses a running release whose notes are missing or cannot be read', function (mixed $groups, string $said): void {
    $running = ['version' => '0.16.0', 'tag' => 'v0.16.0', 'user_facing' => false, 'groups' => $groups];

    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload(changelog: ['running' => $running]))))
        ->toThrow(VersionsAreUnreadable::class, $said);
})->with([
    'no groups' => [null, '`groups`'],
    'a group that is not one' => [['New'], 'Group 1'],
    'a group without entries' => [[['title' => 'New']], 'Group 1'],
    'a group without a title' => [[['entries' => []]], 'Group 1'],
    'entries that are not a list' => [[['title' => 'New', 'entries' => 'many']], 'Group 1'],
    'an entry with no summary' => [[['title' => 'New', 'entries' => [['requirements' => []]]]], 'Group 1'],
    'an entry that is not one' => [[['title' => 'New', 'entries' => ['plain']]], 'Group 1'],
    'the second group' => [[['title' => 'New', 'entries' => []], ['title' => 'Fixed']], 'Group 2'],
]);

it('refuses a group with a blank title rather than drawing an empty heading', function (): void {
    $running = ['version' => '0.16.0', 'tag' => 'v0.16.0', 'user_facing' => false, 'groups' => [['title' => ' ', 'entries' => []]]];

    expect(static fn(): WhatRunsHere => TheVersions::in(versionsSaying(aVersionPayload(changelog: ['running' => $running]))))
        ->toThrow(VersionIsBlank::class);
});

it('refuses a payload that is not an object', function (): void {
    expect(static fn(): WhatRunsHere => TheVersions::in(new Envelope(1, 'version', 'nothing')))
        ->toThrow(VersionsAreUnreadable::class, '`data`');
});

it('starts from a payload the contract would accept', function (): void {
    // Every refusal above spoils this one payload in one place, so it has to be
    // one a stack would send before it is spoiled.
    expect(WhatTheContractAccepts::complaintsAbout('VersionEnvelope', ['api_version' => 1, 'kind' => 'version', 'data' => aVersionPayload()]))
        ->toBe([]);
});
