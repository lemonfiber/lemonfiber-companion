<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowAFoundTitleReads;
use Modules\Household\Internal\Screens\AskingForATitle;
use Modules\Household\Internal\Screens\LookingForATitle;
use Modules\Household\Internal\ThePicturesItDraws;
use Modules\Household\Internal\ViewModels\AFoundTitleAsShown;
use Modules\Household\Internal\WhatAFoundTitleIsOpenedWith;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\Artwork;
use Modules\Kernel\Api\ArtworkKey;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\HowArtworkCame;
use Modules\Kernel\Api\HowASearchWent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAnAskBecame;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ArtworkHeldInMemory;
use Tests\Support\Fakes\AStackThatFindsTitles;
use Tests\Support\Fakes\AStackThatTakesAsks;
use Tests\Support\Fakes\AStackWithPosters;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\ThePicturesOnScreen;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The house a member searches from their phone. */
function theHouseSearchedFrom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('h', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** A keychain holding a member's session for the house, or an operator's where a test says. */
function aKeychainSignedInAs(Whose $whose): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theHouseSearchedFrom()->id(), Session::of('a-session-not-a-secret'), $whose);

    return $keychain;
}

/** The Search tab, asking the house given. */
function theSearchTab(AStackThatFindsTitles $finding, ?AKeychainInMemory $keychain = null, ?ThePicturesItDraws $pictures = null): LookingForATitle
{
    $keychain ??= aKeychainSignedInAs(Whose::member('sanne'));
    $screen = new LookingForATitle($finding, $pictures ?? ThePicturesOnScreen::none(), $keychain, AroundThePhone::holding(StacksInMemory::holding(theHouseSearchedFrom()), storage: $keychain), new AppsSettingsThatOpen());
    $screen->setParams(['stack' => theHouseSearchedFrom()->id()->stored()]);

    return $screen;
}

/** A film the member may ask for. */
function dune(): AFoundTitle
{
    return AFoundTitle::listed('438631', KindOfTitle::Film, 'Dune', WhenItCameOut::in(2021), WhereAFoundTitleStands::Askable);
}

/** A first page, with another after it. */
function aFirstPage(): WhatASearchFound
{
    return WhatASearchFound::before(
        WhereTheNextPageBegins::at('page-2'),
        'What you ask for arrives without waiting for a yes.',
        dune(),
        AFoundTitle::listed('90228', KindOfTitle::Series, 'Dune: Prophecy', WhenItCameOut::unstated(), WhereAFoundTitleStands::OnItsWay),
    );
}

/** The last page. */
function aLastPage(): WhatASearchFound
{
    return WhatASearchFound::lastly('What you ask for arrives without waiting for a yes.', AFoundTitle::listed('693134', KindOfTitle::Film, 'Dune: Part Two', WhenItCameOut::in(2024), WhereAFoundTitleStands::Here));
}

/** A found title's page, opened from its search row, asking the house given. */
function theFoundPage(AStackThatTakesAsks $asking, AFoundTitle $title, bool $carried = true, ?ThePicturesItDraws $pictures = null): AskingForATitle
{
    $keychain = aKeychainSignedInAs(Whose::member('sanne'));
    $screen = new AskingForATitle($asking, $pictures ?? ThePicturesOnScreen::none(), $keychain, SequencedEntropy::counting(), AroundThePhone::holding(StacksInMemory::holding(theHouseSearchedFrom()), storage: $keychain), new AppsSettingsThatOpen());
    $screen->setParams(['stack' => theHouseSearchedFrom()->id()->stored(), 'service' => $title->id()]);

    if ($carried) {
        $screen->setData([
            WhatAFoundTitleIsOpenedWith::Kind->value => $title->kind()->value,
            WhatAFoundTitleIsOpenedWith::Title->value => $title->title(),
            WhatAFoundTitleIsOpenedWith::Year->value => $title->year()->either(dated: static fn(int $year): TheWordCarriedOut => new TheWordCarriedOut((string) $year), unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut(''))->said,
            WhatAFoundTitleIsOpenedWith::Stands->value => $title->stands()->value,
        ]);
    }

    return $screen;
}

/**
 * One line of the catalogue, filled.
 *
 * @param array<string, string> $filling
 */
function aSearchLine(string $key, array $filling = []): string
{
    $line = __($key, $filling);

    return is_string($line) ? $line : '';
}

/** A rehearsal answered in the core's words. */
function aRehearsalSaid(string $said): HowAnAskWent
{
    return HowAnAskWent::answered(WhatAnAskCameTo::answered(WhatAnAskBecame::Waiting, WhetherItWasRehearsed::Rehearsed, $said));
}

it('asks nothing until the member searches, and nothing for fewer words than the core searches for', function (): void {
    $finding = AStackThatFindsTitles::finding(aFirstPage());
    $screen = theSearchTab($finding);

    expect($screen->answer()->searched)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.search.explained'));

    $screen->typed = ' d ';
    $screen->search();

    expect($screen->maySearch())->toBeFalse()
        ->and($finding->asked())->toBe([]);
});

it('shows every title on the page with its year, kind and standing, under the core\'s sentence on approval', function (): void {
    $screen = theSearchTab(AStackThatFindsTitles::finding(aFirstPage()));
    $screen->typed = '  dune ';
    $screen->search();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(
        'What you ask for arrives without waiting for a yes.',
        'Dune',
        aSearchLine('household.search.line', ['year' => '2021', 'kind' => aSearchLine('household.medium.film'), 'stands' => aSearchLine('household.search.askable')]),
        'Dune: Prophecy',
        aSearchLine('household.search.line_undated', ['kind' => aSearchLine('household.medium.series'), 'stands' => aSearchLine('household.asked.getting')]),
    )
        ->and($screen->goes()->found($screen->answer()->rows[0]->found))->toBe(AStacksScreen::Found->forTheStacksFoundTitle(theHouseSearchedFrom()->id(), dune()))
        ->and($screen->answer()->rows[0]->carries)->toBe([
            WhatAFoundTitleIsOpenedWith::Kind->value => 'movies',
            WhatAFoundTitleIsOpenedWith::Title->value => 'Dune',
            WhatAFoundTitleIsOpenedWith::Year->value => '2021',
            WhatAFoundTitleIsOpenedWith::Stands->value => 'askable',
        ]);
});

it('reads the next page as the member reaches the end, from the place the last page named, and adds it beneath', function (): void {
    $finding = AStackThatFindsTitles::finding(aFirstPage(), aLastPage());
    $screen = theSearchTab($finding);
    $screen->typed = 'dune';
    $screen->search();
    $screen->typed = 'something else';
    $screen->more();
    $screen->more();

    expect($finding->asked())->toBe(['dune', 'dune after page-2'])
        ->and(array_map(static fn(AFoundTitleAsShown $row): string => $row->titled, $screen->answer()->rows))->toBe(['Dune', 'Dune: Prophecy', 'Dune: Part Two'])
        ->and($screen->answer()->hasMore())->toBeFalse();
});

it('says in as many words that nothing matched, rather than drawing a blank list', function (): void {
    $screen = theSearchTab(AStackThatFindsTitles::finding(WhatASearchFound::lastly('Asks wait for a yes.')));
    $screen->typed = 'zzzz';
    $screen->search();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.search.nothing_found', ['words' => 'zzzz']));
});

it('draws what stopped a search in the core\'s words, and offers searching again', function (): void {
    $screen = theSearchTab(AStackThatFindsTitles::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen->typed = 'dune';
    $screen->search();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->stopped?->met)->toBe(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold())
        ->and($drawn->offers())->toContain(__('household.search.again'));
});

it('lets go of a refused session and offers signing in again', function (): void {
    $keychain = aKeychainSignedInAs(Whose::member('sanne'));
    $screen = theSearchTab(AStackThatFindsTitles::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain);
    $screen->typed = 'dune';
    $screen->search();

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.signed_out'));
});

it('rehearses on its first frame and shows the core\'s sentence on approval and allowance before the member asks', function (): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('Asking for Dune waits for a yes, and leaves room for 1 more film.'), aRehearsalSaid('unused'));
    $page = theFoundPage($asking, dune());
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain('Dune', 'Asking for Dune waits for a yes, and leaves room for 1 more film.')
        ->and($drawn->offers())->toContain(__('household.found.ask'))
        ->and($asking->rehearsed())->toBe(['movies 438631'])
        ->and($asking->asked())->toBe([]);
});

it('asks once under one key, keeps the key while no answer came, and lets it go once one did', function (): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('It waits for a yes.'), HowAnAskWent::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $page = theFoundPage($asking, dune());
    $page->ask();

    expect(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__('household.found.may_have_gone'));

    $asking->answerTheAskWith(HowAnAskWent::answered(WhatAnAskCameTo::answered(WhatAnAskBecame::Waiting, WhetherItWasRehearsed::CarriedOut, 'Dune is asked for, and waits for a yes.')));
    $page->ask();
    [$first, $again] = $asking->asked();

    expect($first)->toBe($again)
        ->and($page->key)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->toContain('Dune is asked for, and waits for a yes.')
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->toContain(aSearchLine('household.search.line', ['year' => '2021', 'kind' => aSearchLine('household.medium.film'), 'stands' => aSearchLine('household.found.became.waiting')]));
});

it('draws where a title stands as its one action, not usable, where it cannot be asked for', function (WhereAFoundTitleStands $stands): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('unused'), aRehearsalSaid('unused'));
    $page = theFoundPage($asking, AFoundTitle::listed('1', KindOfTitle::Film, 'Dune', WhenItCameOut::unstated(), $stands));
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(__(new HowAFoundTitleReads()->stands($stands)))
        ->and($drawn->offersThatWait())->toContain(__(new HowAFoundTitleReads()->stands($stands)))
        ->and($asking->rehearsed())->toBe([]);
})->with([
    'here' => [WhereAFoundTitleStands::Here],
    'partly here' => [WhereAFoundTitleStands::PartlyHere],
    'on its way' => [WhereAFoundTitleStands::OnItsWay],
]);

it('opened with nothing carried, says to open it from Search and asks nothing', function (): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('unused'), aRehearsalSaid('unused'));
    $page = theFoundPage($asking, dune(), carried: false);

    expect(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__('household.found.from_search'))
        ->and($asking->rehearsed())->toBe([]);
});

it('keeps the titles already shown and draws what stopped the next page beneath them', function (): void {
    $screen = theSearchTab(AStackThatFindsTitles::answering(HowASearchWent::found(aFirstPage()), HowASearchWent::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $screen->typed = 'dune';
    $screen->search();
    $screen->more();

    expect(array_map(static fn(AFoundTitleAsShown $row): string => $row->titled, $screen->answer()->rows))->toBe(['Dune', 'Dune: Prophecy'])
        ->and($screen->answer()->stopped?->met)->toBe(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold())
        ->and($screen->answer()->hasMore())->toBeFalse();
});

it('opened with a blank title carried, says to open it from Search, and asks nothing when told to ask', function (): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('unused'), aRehearsalSaid('unused'));
    $page = theFoundPage($asking, dune(), carried: false);
    $page->setData([
        WhatAFoundTitleIsOpenedWith::Kind->value => KindOfTitle::Film->value,
        WhatAFoundTitleIsOpenedWith::Title->value => '  ',
        WhatAFoundTitleIsOpenedWith::Year->value => '',
        WhatAFoundTitleIsOpenedWith::Stands->value => WhereAFoundTitleStands::Askable->value,
    ]);

    $page->ask();

    expect($page->title())->toBeNull()
        ->and($page->shown())->toBeNull()
        ->and($asking->asked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__('household.found.from_search'));
});

it('rehearses again when asked to check again', function (): void {
    $asking = AStackThatTakesAsks::answering(aRehearsalSaid('Asks wait for a yes.'), aRehearsalSaid('unused'));
    $page = theFoundPage($asking, dune());
    $page->rehearsal();

    $page->askAgain();
    $page->rehearsal();

    expect($asking->rehearsed())->toHaveCount(2);
});

it('draws each title\'s poster from what the phone holds, fetched once, and letters a title with none', function (): void {
    $held = ArtworkHeldInMemory::working();
    $posters = AStackWithPosters::answering(HowArtworkCame::arrived(Artwork::of('a poster')));
    $screen = theSearchTab(AStackThatFindsTitles::finding(aFirstPage(), aFirstPage()), pictures: new ThePicturesItDraws($held, $posters));
    $screen->typed = 'dune';
    $screen->search();
    $screen->search();

    $sources = array_map(static fn(AFoundTitleAsShown $row): string => $row->pictured, $screen->answer()->rows);

    expect($sources)->each->toStartWith(ArtworkKey::SCHEME)
        ->and($posters->asked())->toBe(2)
        ->and($held->howMany())->toBe(2);

    $lettered = theSearchTab(AStackThatFindsTitles::finding(aFirstPage()));
    $lettered->typed = 'dune';
    $lettered->search();

    expect(array_map(static fn(AFoundTitleAsShown $row): string => $row->pictured, $lettered->answer()->rows))->each->toBe('');
});

it('draws the found title\'s poster from what the phone holds, and none where it was opened with nothing', function (): void {
    $held = ArtworkHeldInMemory::working();
    $posters = AStackWithPosters::answering(HowArtworkCame::arrived(Artwork::of('a poster')));
    $page = theFoundPage(AStackThatTakesAsks::answering(aRehearsalSaid('It arrives without waiting.'), aRehearsalSaid('unused')), dune(), pictures: new ThePicturesItDraws($held, $posters));
    $drawn = WhatTheDeviceWouldDraw::by($page)->said();

    expect($page->pictured())->toStartWith(ArtworkKey::SCHEME)
        ->and($drawn)->toContain('Dune')
        ->and(theFoundPage(AStackThatTakesAsks::answering(aRehearsalSaid('It arrives without waiting.'), aRehearsalSaid('unused')), dune(), carried: false, pictures: new ThePicturesItDraws($held, $posters))->pictured())->toBe('')
        ->and($posters->asked())->toBe(1);
});
