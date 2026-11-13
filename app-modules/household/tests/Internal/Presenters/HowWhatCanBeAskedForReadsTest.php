<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowWhatCanBeAskedForReads;
use Modules\Household\Internal\ViewModels\AChoiceAsShown;
use Modules\Household\Internal\ViewModels\WhatCanBeSearchedAsShown;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\UnitOfTitle;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

/**
 * Each chip, as its key and the word it hands back, marked where chosen. Named for this file.
 *
 * @param list<AChoiceAsShown> $chips
 *
 * @return list<string>
 */
function theChipsOffered(array $chips): array
{
    return array_map(static fn(AChoiceAsShown $chip): string => sprintf('%s=%s%s', $chip->said, $chip->word, $chip->chosen ? '*' : ''), $chips);
}

/** The chips for what a house lists, with this chosen. */
function theChipsFor(WhatCanBeAskedFor $listed, string $chosen): WhatCanBeSearchedAsShown
{
    return new HowWhatCanBeAskedForReads()->chips($listed, $listed->named($chosen));
}

it('offers a chip for each kind listed and each unit of the kind chosen, marking the ones chosen', function (): void {
    $listed = WhatCanBeAskedFor::listing()
        ->and(KindOfTitle::Film)
        ->and(KindOfTitle::Music, UnitOfTitle::Album, UnitOfTitle::Artist)
        ->and(KindOfTitle::Books, UnitOfTitle::Book);
    $chips = theChipsFor($listed, 'music/artist');

    expect(theChipsOffered($chips->kinds))->toBe([
        'household.search.among.films-and-series=films-and-series',
        'household.search.among.music=music*',
        'household.search.among.books=books',
    ])
        ->and(theChipsOffered($chips->units))->toBe(['household.search.unit.album=music/album', 'household.search.unit.artist=music/artist*'])
        ->and($chips->explained)->toBe('household.search.explained_everything')
        ->and($chips->placeholder)->toBe('household.search.placeholder_everything')
        ->and(theChipsFor($listed, 'books')->units)->toBe([])
        ->and(theChipsFor($listed, '')->units)->toBe([]);
});

it('draws no row of one chip, and says the tab is for films and series where that is all it offers', function (WhatCanBeAskedFor $listed, string $explained): void {
    $chips = theChipsFor($listed, '');

    expect($chips->kinds)->toBe([])
        ->and($chips->units)->toBe([])
        ->and($chips->explained)->toBe($explained);
})->with([
    'films and series alone' => [WhatCanBeAskedFor::filmsAndSeriesAlone(), 'household.search.explained'],
    'nothing listed' => [WhatCanBeAskedFor::listing(), 'household.search.explained'],
    'one album and nothing else' => [WhatCanBeAskedFor::listing()->and(KindOfTitle::Music, UnitOfTitle::Album), 'household.search.explained_everything'],
]);

it('says every chip in every language, each in words of its own', function (string $locale): void {
    app()->setLocale($locale);
    $listed = WhatCanBeAskedFor::listing()
        ->and(KindOfTitle::Series)
        ->and(KindOfTitle::Music, UnitOfTitle::Album, UnitOfTitle::Artist)
        ->and(KindOfTitle::Books, UnitOfTitle::Book, UnitOfTitle::Author, UnitOfTitle::Series);
    $keys = [
        ...array_map(static fn(AChoiceAsShown $chip): string => $chip->said, theChipsFor($listed, '')->kinds),
        ...array_map(static fn(AChoiceAsShown $chip): string => $chip->said, theChipsFor($listed, 'music')->units),
        ...array_map(static fn(AChoiceAsShown $chip): string => $chip->said, theChipsFor($listed, 'books')->units),
        ...array_map(static fn(UnitOfTitle $unit): string => $unit->saidToTheMember(), UnitOfTitle::cases()),
        HowWhatCanBeAskedForReads::EXPLAINED_EVERYTHING,
        HowWhatCanBeAskedForReads::PLACEHOLDER_EVERYTHING,
    ];

    foreach ($keys as $key) {
        expect(__($key))->not->toBe($key, sprintf('%s is not in the %s catalogue', $key, $locale));
    }
})->with(['en', 'nl']);
