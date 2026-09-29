<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Sdk\Api\CatalogueIsUnreadable;
use Modules\Sdk\Api\Catalogues;
use Modules\Sdk\Api\Fields\CatalogueField;
use Modules\Sdk\Api\WireField;
use Tests\Support\WhatTheContractAccepts;

it('names the list a catalogue left out', function (): void {
    expect(CatalogueIsUnreadable::missing(CatalogueField::Removed)->getMessage())
        ->toBe('The catalogue envelope has no `removed`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names the list, the row and the field of an entry it could not read', function (): void {
    expect(CatalogueIsUnreadable::entry(WireField::Services, CatalogueField::WithoutIt, 3)->getMessage())
        ->toBe('Row 3 of `services` in the catalogue envelope has no readable `without_it`. It is refused rather than dropped: a catalogue one row short says the stack does not carry something it does.');
});

it('names every word for how much a service matters when refusing one it does not read', function (): void {
    expect(CatalogueIsUnreadable::matters('vital', 0)->getMessage())
        ->toBe('Row 0 of `services` in the catalogue envelope says it matters `vital`, and this app reads `critical`, `core`, `important`, `enhancing`, `optional`.');
});

/**
 * What a stack says of its catalogue, two rows to each list, with either list changed where a case says.
 *
 * @param  array<mixed> $changed
 * @return array<string, mixed>
 */
function aCatalogueOfTwoRows(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'catalogue', 'data' => [
        'services' => [
            ['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds and fetches television', 'without_it' => 'New episodes stop arriving', 'criticality' => 'important'],
            ['id' => 'bazarr', 'name' => 'Bazarr', 'describes' => 'Finds subtitles', 'without_it' => 'Nothing has subtitles', 'criticality' => 'optional'],
        ],
        'removed' => [
            ['id' => 'ombi', 'removed_in' => '0.8.0', 'reason' => 'Requests moved into the household app', 'replaced_by' => 'jellyseerr'],
            ['id' => 'lidarr', 'removed_in' => '0.9.0', 'reason' => 'Music is handled elsewhere', 'replaced_by' => null],
        ],
        ...$changed,
    ]];
}

it('stands in for a stack with a catalogue the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CatalogueEnvelope', aCatalogueOfTwoRows()))->toBe([]);
});

it('names the row the reader stopped at, counted from nought in the stack\'s order', function (array $changed, string $row): void {
    expect(static fn(): TheCatalogue => Catalogues::in(new Envelope(1, 'catalogue', aCatalogueOfTwoRows($changed)['data'])))->toThrow(CatalogueIsUnreadable::class, $row);
})->with([
    [['services' => [
        ['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds and fetches television', 'without_it' => 'New episodes stop arriving', 'criticality' => 'important'],
        ['id' => 'bazarr', 'name' => ' ', 'describes' => 'Finds subtitles', 'without_it' => 'Nothing has subtitles', 'criticality' => 'optional'],
    ]], 'Row 1 of `services` in the catalogue envelope has no readable `name`'],
    [['services' => [
        ['id' => 'sonarr', 'name' => 'Sonarr', 'describes' => 'Finds and fetches television', 'without_it' => 'New episodes stop arriving', 'criticality' => 'important'],
        ['id' => 'bazarr', 'name' => 'Bazarr', 'describes' => 'Finds subtitles', 'without_it' => 'Nothing has subtitles', 'criticality' => 'vital'],
    ]], 'Row 1 of `services` in the catalogue envelope says it matters `vital`'],
    [['removed' => [
        ['id' => 'ombi', 'removed_in' => '0.8.0', 'reason' => 'Requests moved into the household app', 'replaced_by' => 'jellyseerr'],
        ['id' => 'lidarr', 'removed_in' => '0.9.0', 'reason' => ' ', 'replaced_by' => null],
    ]], 'Row 1 of `removed` in the catalogue envelope has no readable `reason`'],
]);
