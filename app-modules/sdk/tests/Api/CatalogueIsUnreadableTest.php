<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\CatalogueIsUnreadable;
use Modules\Sdk\Api\Fields\CatalogueField;
use Modules\Sdk\Api\WireField;

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
