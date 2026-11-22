<?php

declare(strict_types=1);

use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatIsLeftAlone;
use Tests\Support\TheGeneratedEnvelopes;
use Tests\Support\TheWireUnions;

it('every cost the contract describes has a case', function (): void {
    $costs = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedConfigEnvelope(), 'cost');

    expect($costs)->not->toBe([], 'no cost union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Cost::cases()))->toBe($costs);
});

it('every stance the contract describes has a case', function (): void {
    // The one where a missing case would be worst. A stance this app did not
    // know would be refused as unreadable, which is correct — but the reason
    // to hold the set to the wire here is the pair the enum exists to keep
    // apart: `unchanged` and `applied` both mean the setting holds what was
    // asked for, and a contract that grew a third of those would need reading
    // rather than guessing at.
    $stances = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedConfigEnvelope(), 'stance');

    expect($stances)->not->toBe([], 'no stance union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(Stance::cases()))->toBe($stances);
});

it('every kind of thing left alone has a case', function (): void {
    $kinds = TheWireUnions::unionIn(TheGeneratedEnvelopes::theGeneratedConfigEnvelope(), 'kind');

    expect($kinds)->not->toBe([], 'no kind union was found in the generated envelope');
    expect(TheWireUnions::valuesOf(WhatIsLeftAlone::cases()))->toBe($kinds);
});
