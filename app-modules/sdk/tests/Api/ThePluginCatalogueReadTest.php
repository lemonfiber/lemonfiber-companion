<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ThePluginCatalogueIsUnreadable;
use Modules\Sdk\Api\PluginCatalogueIsUnreadable;
use Modules\Sdk\Api\ThePluginCatalogueRead;

/**
 * A `plugin-catalogue` envelope holding whatever the case under test is about.
 *
 * `plugin-catalogue` is stood in for and not judged: each case spoils one
 * field the contract fixes, and the payloads a stack does send are judged in
 * `ReadingThePluginCatalogueContractTest`.
 *
 * @return Envelope<mixed>
 */
function aPluginCatalogueEnvelope(mixed $data): Envelope
{
    return new Envelope(1, 'plugin-catalogue', $data);
}

/**
 * One entry, as the wire carries it, with these fields changed.
 *
 * @param array<string, mixed> $with
 * @return array<string, mixed>
 */
function aCatalogueEntryOnTheWire(array $with = []): array
{
    return [...['id' => 'bazarr', 'name' => 'Bazarr', 'description' => 'Fetches subtitles', 'version' => '1.4.0', 'without_it' => 'Nothing comes with subtitles'], ...$with];
}

/**
 * A listed catalogue holding these entries.
 *
 * @param list<mixed> $entries
 * @return array<string, mixed>
 */
function aCatalogueListing(array $entries): array
{
    return ['state' => 'listed', 'published' => 1_791_579_600, 'serial' => 12, 'unreadable' => 0, 'entries' => $entries];
}

it('names the field it could not read, where it sits', function (mixed $data, string $message): void {
    expect(static fn(): mixed => ThePluginCatalogueRead::in(aPluginCatalogueEnvelope($data)))
        ->toThrow(PluginCatalogueIsUnreadable::class, $message);
})->with([
    'no payload' => [null, 'The plugin-catalogue envelope has no readable `data`. This answer did not come from a lemonfiber of a version this app can read.'],
    'no state' => [['entries' => []], 'no readable `state`'],
    'a state it has no case for' => [['state' => 'paused', 'entries' => []], 'The plugin-catalogue envelope says its `state` is `paused`, and this app reads `listed`, `off`, `failed`.'],
    'no date' => [['state' => 'listed', 'serial' => 1, 'unreadable' => 0, 'entries' => []], 'no readable `published`'],
    'no serial' => [['state' => 'listed', 'published' => 1, 'unreadable' => 0, 'entries' => []], 'no readable `serial`'],
    'a serial below nought' => [['state' => 'listed', 'published' => 1, 'serial' => -1, 'unreadable' => 0, 'entries' => []], 'no readable `serial`'],
    'no count of what it could not list' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'entries' => []], 'no readable `unreadable`'],
    'no entries' => [['state' => 'listed', 'published' => 1, 'serial' => 1, 'unreadable' => 0], 'no readable `entries`'],
    'an entry that is not a table' => [aCatalogueListing(['bazarr']), 'no readable `entries`'],
    'an entry with no id' => [aCatalogueListing([aCatalogueEntryOnTheWire(['id' => ''])]), 'no readable `entries.id`'],
    'an entry with no name' => [aCatalogueListing([aCatalogueEntryOnTheWire(['name' => ' '])]), 'no readable `entries.name`'],
    'an entry with no description' => [aCatalogueListing([aCatalogueEntryOnTheWire(['description' => null])]), 'no readable `entries.description`'],
    'an entry with no version' => [aCatalogueListing([aCatalogueEntryOnTheWire(['version' => 2])]), 'no readable `entries.version`'],
    'an entry with nothing said of going without it' => [aCatalogueListing([aCatalogueEntryOnTheWire(['without_it' => ''])]), 'no readable `entries.without_it`'],
    'an installed version that is not a table' => [aCatalogueListing([aCatalogueEntryOnTheWire(['installed' => '1.4.0'])]), 'no readable `entries.installed`'],
    'an installed version with no version' => [aCatalogueListing([aCatalogueEntryOnTheWire(['installed' => ['newer' => false]])]), 'no readable `installed.version`'],
    'an installed version with no word on newer' => [aCatalogueListing([aCatalogueEntryOnTheWire(['installed' => ['version' => '1.3.0']])]), 'no readable `installed.newer`'],
    'off with no setting' => [['state' => 'off', 'entries' => []], 'no readable `setting`'],
    'off with no key' => [['state' => 'off', 'entries' => [], 'setting' => ['value' => 'on', 'action' => 'config-set']], 'no readable `setting.key`'],
    'off with no value' => [['state' => 'off', 'entries' => [], 'setting' => ['key' => 'LEMONFIBER_OFFLINE', 'action' => 'config-set']], 'no readable `setting.value`'],
    'off with no act that writes it' => [['state' => 'off', 'entries' => [], 'setting' => ['key' => 'LEMONFIBER_OFFLINE', 'value' => 'off']], 'no readable `setting.action`'],
    'failed with no failure' => [['state' => 'failed', 'entries' => []], 'no readable `failure`'],
    'failed with no code' => [['state' => 'failed', 'entries' => [], 'failure' => ['summary' => 'It did not verify']], 'no readable `failure.code`'],
    'failed with no summary' => [['state' => 'failed', 'entries' => [], 'failure' => ['code' => 'catalogue_unverified']], 'no readable `failure.summary`'],
]);

it('refuses a setting written by an act this app does not put to a review', function (): void {
    expect(static fn(): mixed => ThePluginCatalogueRead::in(aPluginCatalogueEnvelope(['state' => 'off', 'entries' => [], 'setting' => ['key' => 'LEMONFIBER_OFFLINE', 'value' => 'off', 'action' => 'settings-set']])))
        ->toThrow(ThePluginCatalogueIsUnreadable::class, '`settings-set`');
});

it('reads an entry whose installed version is null as not installed', function (): void {
    $catalogue = ThePluginCatalogueRead::in(aPluginCatalogueEnvelope(aCatalogueListing([aCatalogueEntryOnTheWire(['installed' => null])])));

    expect($catalogue->either(
        listed: static fn(mixed $published, int $serial, int $unreadable, array $entries): ArrayObject => new ArrayObject([$entries[0]->installed(), $entries[0]->isNewer()]),
        switchedOff: static fn(): ArrayObject => new ArrayObject(['off']),
        unreached: static fn(): ArrayObject => new ArrayObject(['failed']),
    )->getArrayCopy())->toBe(['', false]);
});
