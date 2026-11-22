<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Kernel\Api\ThePluginCatalogueIsUnreadable;
use Modules\Kernel\Api\WhatWasFoundOfThePluginCatalogue;

use function sprintf;

/** An entry the catalogue offers, not installed here. */
function anEntry(): ACatalogueEntry
{
    return ACatalogueEntry::offered(' bazarr ', ' Bazarr ', ' Finds subtitles for films and series. ', ' 1.4.0 ', ' Films and series play without subtitles. ');
}

/** The same entry, installed here at that version. */
function anEntryInstalledAt(string $installed, bool $newer): ACatalogueEntry
{
    return ACatalogueEntry::installedHere(' bazarr ', 'Bazarr', 'Finds subtitles for films and series.', '1.4.0', 'Films and series play without subtitles.', $installed, $newer);
}

/**
 * Which arm a catalogue took, with what it carried.
 *
 * @return array<int, int|string>
 */
function whichCatalogueArm(ThePluginCatalogue $catalogue): array
{
    return $catalogue->either(
        listed: static fn(Instant $published, int $serial, int $unreadable, array $entries): ArrayObject => new ArrayObject(['listed', $published->epochSeconds(), $serial, $unreadable, count($entries)]),
        switchedOff: static fn(ASettingThatReaches $setting): ArrayObject => new ArrayObject(['off', $setting->key(), $setting->value()]),
        unreached: static fn(string $code, string $summary): ArrayObject => new ArrayObject(['failed', $code, $summary]),
    )->getArrayCopy();
}

it('keeps every word of an entry, trimmed, and says it is not installed', function (): void {
    $entry = anEntry();

    expect([$entry->id(), $entry->name(), $entry->description(), $entry->version(), $entry->withoutIt(), $entry->installed(), $entry->isNewer()])
        ->toBe(['bazarr', 'Bazarr', 'Finds subtitles for films and series.', '1.4.0', 'Films and series play without subtitles.', '', false]);
});

it('keeps the version installed here and the stack\'s word on whether the catalogue\'s is newer', function (): void {
    $older = anEntryInstalledAt(' 1.3.2 ', newer: true);
    $same = anEntryInstalledAt('1.4.0', newer: false);

    expect([$older->installed(), $older->isNewer(), $older->version()])->toBe(['1.3.2', true, '1.4.0'])
        ->and([$same->installed(), $same->isNewer()])->toBe(['1.4.0', false]);
});

it('refuses an entry with any word blank, naming it', function (string $field, string $id, string $name, string $description, string $version, string $withoutIt): void {
    expect(static fn(): ACatalogueEntry => ACatalogueEntry::offered($id, $name, $description, $version, $withoutIt))
        ->toThrow(ThePluginCatalogueIsUnreadable::class, sprintf('`%s`', $field));
})->with([
    'id' => ['id', ' ', 'Bazarr', 'd', '1', 'w'],
    'name' => ['name', 'b', '', 'd', '1', 'w'],
    'description' => ['description', 'b', 'B', ' ', '1', 'w'],
    'version' => ['version', 'b', 'B', 'd', '', 'w'],
    'without it' => ['without_it', 'b', 'B', 'd', '1', ' '],
]);

it('refuses an installed version that is blank', function (): void {
    expect(static fn(): ACatalogueEntry => anEntryInstalledAt(' ', newer: false))
        ->toThrow(ThePluginCatalogueIsUnreadable::class, '`installed.version`');
});

it('installs a chosen entry by the name it is installed by, as a typed name would be', function (): void {
    expect(APluginSource::listed(anEntry())->said())->toBe(APluginSource::typed('bazarr')->said());
});

it('keeps the setting the stack names, written by the reviewed setting change', function (): void {
    $setting = ASettingThatReaches::named(' LEMONFIBER_REACH_CATALOGUE ', ' on ', 'config-set');

    expect([$setting->key(), $setting->value()])->toBe(['LEMONFIBER_REACH_CATALOGUE', 'on']);
});

it('refuses a setting written by an act this app does not know, or with its key or value blank', function (string $said, string $key, string $value, string $action): void {
    expect(static fn(): ASettingThatReaches => ASettingThatReaches::named($key, $value, $action))
        ->toThrow(ThePluginCatalogueIsUnreadable::class, $said);
})->with([
    'another act' => ['`settings-set`', 'K', 'on', 'settings-set'],
    'a blank key' => ['`setting.key`', ' ', 'on', 'config-set'],
    'a blank value' => ['`setting.value`', 'K', '', 'config-set'],
]);

it('answers each of the three states through its own arm, never an empty list for another', function (): void {
    $listed = ThePluginCatalogue::listed(Instant::atEpochSeconds(1_893_441_600), 42, 2, anEntry(), anEntryInstalledAt('1.3.2', newer: true));
    $off = ThePluginCatalogue::switchedOff(ASettingThatReaches::named('LEMONFIBER_OFFLINE', 'off', 'config-set'));
    $failed = ThePluginCatalogue::unreached(' catalogue_unreachable ', ' The catalogue could not be reached. ');

    expect(whichCatalogueArm($listed))->toBe(['listed', 1_893_441_600, 42, 2, 2])
        ->and(whichCatalogueArm(ThePluginCatalogue::listed(Instant::atEpochSeconds(1), 1, 0)))->toBe(['listed', 1, 1, 0, 0])
        ->and(whichCatalogueArm($off))->toBe(['off', 'LEMONFIBER_OFFLINE', 'off'])
        ->and(whichCatalogueArm($failed))->toBe(['failed', 'catalogue_unreachable', 'The catalogue could not be reached.']);
});

it('refuses a failure with its code or summary blank', function (string $field, string $code, string $summary): void {
    expect(static fn(): ThePluginCatalogue => ThePluginCatalogue::unreached($code, $summary))
        ->toThrow(ThePluginCatalogueIsUnreadable::class, sprintf('`%s`', $field));
})->with([
    'code' => ['failure.code', ' ', 'summary'],
    'summary' => ['failure.summary', 'code', ''],
]);

it('tells a catalogue the stack answered from a stack that could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $found = WhatWasFoundOfThePluginCatalogue::found(ThePluginCatalogue::listed(Instant::atEpochSeconds(1), 1, 0));

    expect($found->either(found: static fn(): ArrayObject => new ArrayObject(['found']), met: static fn(): ArrayObject => new ArrayObject(['met']))->getArrayCopy())->toBe(['found'])
        ->and(WhatWasFoundOfThePluginCatalogue::met($why)->either(found: static fn(): ArrayObject => new ArrayObject(['found']), met: static fn(Obstacle $met): ArrayObject => new ArrayObject([$met]))->getArrayCopy())->toBe([$why]);
});
