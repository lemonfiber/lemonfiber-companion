<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\ChoicesOfTitles;
use Modules\Kernel\Api\HowTheKindsWent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\UnitOfTitle;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhichTitles;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What a house with a request service and curators for music and books lists, in the core's order. */
function everythingListed(): WhatCanBeAskedFor
{
    return WhatCanBeAskedFor::listing()
        ->and(KindOfTitle::Series)
        ->and(KindOfTitle::Film)
        ->and(KindOfTitle::Music, UnitOfTitle::Album, UnitOfTitle::Artist)
        ->and(KindOfTitle::Books, UnitOfTitle::Book, UnitOfTitle::Author, UnitOfTitle::Series);
}

/**
 * Each choice, by the word a member chooses it by.
 *
 * @return list<string>
 */
function theWords(ChoicesOfTitles $choices): array
{
    return array_map(static fn(WhichTitles $choice): string => $choice->word(), [...$choices]);
}

it('offers films and series as one choice, and each unit of music and books as its own', function (): void {
    expect(theWords(everythingListed()->kinds()))->toBe(['films-and-series', 'music/album', 'books/book'])
        ->and(theWords(everythingListed()->unitsBeside(WhichTitles::of(KindOfTitle::Books, UnitOfTitle::Author))))->toBe(['books/book', 'books/author', 'books/series'])
        ->and(everythingListed()->unitsBeside(WhichTitles::filmsAndSeries())->count())->toBe(0)
        ->and(everythingListed()->offered()->count())->toBe(6);
});

it('offers nothing of a kind of music or books that lists no unit', function (): void {
    $listed = WhatCanBeAskedFor::listing()->and(KindOfTitle::Music)->and(KindOfTitle::Books, UnitOfTitle::Book);

    expect(theWords($listed->kinds()))->toBe(['books/book']);
});

it('starts on the first choice offered, and on films and series where nothing is offered', function (): void {
    expect(WhatCanBeAskedFor::listing()->and(KindOfTitle::Music, UnitOfTitle::Artist)->offered()->first()->word())->toBe('music/artist')
        ->and(WhatCanBeAskedFor::listing()->offered()->first()->word())->toBe('films-and-series')
        ->and(WhatCanBeAskedFor::filmsAndSeriesAlone()->offered()->first()->word())->toBe('films-and-series')
        ->and(theWords(WhatCanBeAskedFor::filmsAndSeriesAlone()->offered()))->toBe(['films-and-series']);
});

it('tells films and series from a unit of music or books, and one choice from another', function (): void {
    $album = WhichTitles::of(KindOfTitle::Music, UnitOfTitle::Album);

    expect($album->kindWord())->toBe('music')
        ->and($album->unitWord())->toBe('album')
        ->and(WhichTitles::filmsAndSeries()->kindWord())->toBe('films-and-series')
        ->and(WhichTitles::filmsAndSeries()->unitWord())->toBe('')
        ->and($album->is(WhichTitles::of(KindOfTitle::Music, UnitOfTitle::Album)))->toBeTrue()
        ->and($album->is(WhichTitles::of(KindOfTitle::Music, UnitOfTitle::Artist)))->toBeFalse()
        ->and(WhichTitles::filmsAndSeries()->is(WhichTitles::filmsAndSeries()))->toBeTrue()
        ->and($album->either(filmsAndSeries: static fn(): TheWordCarriedOut => new TheWordCarriedOut('both'), of: static fn(KindOfTitle $kind, UnitOfTitle $unit): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s %s', $kind->value, $unit->value)))->said)->toBe('music album');
});

it('looks among films and series until told otherwise, keeping the words', function (): void {
    $words = WhatToLookFor::typed(' rumours ');
    $among = $words->among(WhichTitles::of(KindOfTitle::Music, UnitOfTitle::Album));

    expect($words->which()->word())->toBe('films-and-series')
        ->and($among->which()->word())->toBe('music/album')
        ->and($among->said())->toBe('rumours');
});

it('keeps a title of music or books with its unit and who made it, and names it apart from a film with its id', function (): void {
    $album = AFoundTitle::curated('mb-1', KindOfTitle::Music, UnitOfTitle::Album, 'Rumours', ' Fleetwood Mac ', WhenItCameOut::in(1977), WhereAFoundTitleStands::Askable);
    $film = AFoundTitle::listed('mb-1', KindOfTitle::Film, 'Rumours', WhenItCameOut::unstated(), WhereAFoundTitleStands::Askable);
    $unnamed = AFoundTitle::curated('mb-2', KindOfTitle::Books, UnitOfTitle::Book, 'Untitled', ' ', WhenItCameOut::unstated(), WhereAFoundTitleStands::Here);

    expect($album->among()->word())->toBe('music/album')
        ->and($album->by(named: static fn(string $by): TheWordCarriedOut => new TheWordCarriedOut($by), unnamed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said)->toBe('Fleetwood Mac')
        ->and($unnamed->by(named: static fn(string $by): TheWordCarriedOut => new TheWordCarriedOut($by), unnamed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said)->toBe('-')
        ->and($film->among()->word())->toBe('films-and-series')
        ->and($album->named())->toBe('music|music/album|mb-1')
        ->and($film->named())->toBe('movies|films-and-series|mb-1')
        ->and($album->title())->toBe('Rumours')
        ->and($unnamed->stands())->toBe(WhereAFoundTitleStands::Here);
});

it('takes the arm asking what can be asked for went down, and carries what each held', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(HowTheKindsWent::listed(everythingListed())->either(listed: static fn(WhatCanBeAskedFor $listed): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d', $listed->kinds()->count())), refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'))->said)->toBe('3')
        ->and(HowTheKindsWent::refused($why)->either(listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('listed'), refused: static fn(Obstacle $met): TheWordCarriedOut => new TheWordCarriedOut($met->kind()->value))->said)->toBe($why->kind()->value);
});

it('says each unit to a member by a key of its own', function (): void {
    expect(array_map(static fn(UnitOfTitle $unit): string => $unit->saidToTheMember(), UnitOfTitle::cases()))
        ->toBe(['household.unit.album', 'household.unit.artist', 'household.unit.book', 'household.unit.author', 'household.unit.series']);
});
