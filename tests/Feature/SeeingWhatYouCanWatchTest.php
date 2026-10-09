<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowAShelfReads;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatMarkupDraws;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheKeychainStillHolds;

// What a member already has, as against what they can ask for.
//
// The screen renders the core's answer and nothing else: no row is filtered,
// sorted or hidden here, and the one thing it must get right beyond that is
// telling an empty shelf from a library it could not reach. Those are drawn by
// the same loop and say opposite things to the person reading them.
//
// Here rather than in the household module's own tests because a screen
// renders, and rendering needs the application — `view()` and `__()` are not
// there in a module suite.

/** The machine a member's shelf is read from. */
function theStackAShelfIsReadFrom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A shelf with one of each kind on it, the middle one undated. */
function aShelfOfThree(): Shelf
{
    return Shelf::of(
        Holding::of(HoldingId::called('a1'), 'A film', Medium::Film, WhenItCameOut::in(1999)),
        Holding::of(HoldingId::called('b2'), 'A series', Medium::Series, WhenItCameOut::unstated()),
        Holding::of(HoldingId::called('c3'), 'Something else', Medium::Other, WhenItCameOut::in(2012)),
    );
}

/** The shelf screen, over a fake, with a session unless a test says otherwise. */
function theShelfScreen(
    AShelfThatWasRead $watching,
    bool $signedIn = true,
    ?Whose $whose = null,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
    ?AppsSettingsThatOpen $settings = null,
    ?AMemberWhoIsOwed $owing = null,
): WhatYouCanWatch {
    $stack = theStackAShelfIsReadFrom();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep(
            $stack->id(),
            Session::of('a-session-not-a-secret'),
            $whose ?? Whose::member('ada'),
        );
    }

    $screen = new WhatYouCanWatch($watching, $owing ?? AMemberWhoIsOwed::owedNothing(), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain), $settings ?? new AppsSettingsThatOpen());
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

/**
 * What one row of the shelf screen carries, as one thing read off each poster.
 *
 * @param Closure(WhatOnePosterSays): string $read
 *
 * @return list<string>
 */
function eachPosterIn(WhatYouCanWatch $screen, int $row, Closure $read): array
{
    return array_map($read, $screen->answer()->rows[$row]->posters);
}

/** A poster's title. */
function itsTitle(): Closure
{
    return static fn(WhatOnePosterSays $poster): string => $poster->titled;
}

it('draws the shelf the core listed, in its order and unfiltered, first as what is new', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    expect(eachPosterIn($screen, 0, itsTitle()))->toBe(['A film', 'A series', 'Something else'])
        ->and($screen->answer()->rows[0]->heading)->toBe('household.shelf.new')
        ->and($screen->answer()->cameBack())->toBeTrue();
});

it('follows what is new with a row for each kind the shelf holds, in the order the kinds are declared', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    expect(array_map(static fn(object $row): string => $row->heading, $screen->answer()->rows))->toBe([
        'household.shelf.new',
        Medium::Film->shelvedUnder(),
        Medium::Series->shelvedUnder(),
        Medium::Other->shelvedUnder(),
    ])
        ->and(eachPosterIn($screen, 1, itsTitle()))->toBe(['A film'])
        ->and(eachPosterIn($screen, 2, itsTitle()))->toBe(['A series'])
        ->and(eachPosterIn($screen, 3, itsTitle()))->toBe(['Something else']);
});

it('cuts the first row at the newest few and leaves every holding of a kind in its own row', function (): void {
    $films = [];

    for ($nth = 1; $nth <= HowAShelfReads::NEW_IN_THE_HOUSE + 1; $nth++) {
        $films[] = Holding::of(HoldingId::called(sprintf('f%d', $nth)), sprintf('Film %d', $nth), Medium::Film, WhenItCameOut::in(2000 + $nth));
    }

    $screen = theShelfScreen(AShelfThatWasRead::holding(Shelf::of(...$films)));
    $everyTitle = array_map(static fn(Holding $film): string => $film->titled(), $films);

    expect(eachPosterIn($screen, 0, itsTitle()))->toBe(array_slice($everyTitle, 0, HowAShelfReads::NEW_IN_THE_HOUSE))
        ->and(eachPosterIn($screen, 1, itsTitle()))->toBe($everyTitle)
        ->and($screen->answer()->rows)->toHaveCount(2);
});

it('offers each title as a control that opens it, and Play as one that waits', function (): void {
    $screen = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())));

    expect($screen->offers())->toBe([
        __('household.title.play'),
        __('household.hero.more'),
        'A film, Film, 1999', 'A series, Series', 'Something else, Other, 2012',
        'A film, Film, 1999', 'A series, Series', 'Something else, Other, 2012',
        __('household.ask_again'),
    ])
        ->and($screen->offersThatWait())->toBe([__('household.title.play')]);
});

it('opens each title on its own screen on this machine', function (): void {
    $poster = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()))->answer()->rows[0]->posters[1];

    expect($poster->goes)->toBe(AStacksScreen::Title->forTheStacksTitle(theStackAShelfIsReadFrom()->id(), HoldingId::called('b2')));
});

it('draws the newest title in the house across the screen, with Play that waits and its reason, and More', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $hero = array_search(__('household.hero.reads', ['reads' => 'A film, Film, 1999']), $drawn, strict: true);

    expect($screen->answer()->hero)->toBe($screen->answer()->rows[0]->posters[0])
        ->and($hero)->toBeInt()
        ->and(array_slice($drawn, (int) $hero, 6))->toBe([
            __('household.hero.reads', ['reads' => 'A film, Film, 1999']),
            __('household.hero.above', ['line' => '1999 · Film']),
            'A film',
            __('household.title.play'),
            __('household.title.cannot_play'),
            __('household.hero.more'),
        ]);
});

it('draws no hero over an empty shelf', function (): void {
    $empty = theShelfScreen(AShelfThatWasRead::holdingNothing());

    expect($empty->answer()->hero)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($empty)->said())->not->toContain(__('household.title.play'));
});

/**
 * Where on Home a line is said, refusing a line that is not said at all.
 *
 * @param list<string> $said
 */
function whereHomeSays(array $said, string $line): int
{
    $at = array_search($line, $said, strict: true);

    return is_int($at) ? $at : throw new RuntimeException(sprintf('Home does not say "%s".', $line));
}

/** What a screen reader hears for the hero over the shelf of three. */
function theHeroLabel(): string
{
    $said = __('household.hero.reads', ['reads' => 'A film, Film, 1999']);

    return is_string($said) ? $said : '';
}

/** What a member asked for, one of each standing, in the order the core listed them. */
function whatTheyAskedFor(): Requested
{
    $asked = static fn(int $number, string $title, Waiting $standing): Wanted
        => Wanted::of($number, 'ada', $title, Size::unknown(), HowARequestStands::said($standing));

    return Requested::of(
        $asked(1, 'Dune', Waiting::Here),
        $asked(2, 'Heat', Waiting::Getting),
        $asked(3, 'Severance', Waiting::PartlyHere),
        $asked(4, 'Alien', Waiting::ForApproval),
        $asked(5, 'Gone Girl', Waiting::Gone),
        $asked(6, 'Nobody Knows', Waiting::Failed),
        Wanted::of(7, 'ada', 'Unsaid', Size::unknown(), HowARequestStands::unnamed()),
    );
}

it('leads with what is theirs: what arrived for them, then what is on its way, in the order the core listed them', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), owing: AMemberWhoIsOwed::asking(whatTheyAskedFor()));
    $rows = $screen->theirOwn()->rows;

    expect(array_map(static fn(object $row): string => $row->heading, $rows))->toBe(['household.shelf.ready_for_you', 'household.shelf.on_its_way'])
        ->and(array_map(static fn(WhatOnePosterSays $poster): string => $poster->titled, $rows[0]->posters))->toBe(['Dune', 'Severance'])
        ->and(array_map(static fn(WhatOnePosterSays $poster): string => $poster->titled, $rows[1]->posters))->toBe(['Heat', 'Severance', 'Alien']);
});

it('draws each request as a poster saying where it stands, opening nothing, ahead of the house\'s own', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), owing: AMemberWhoIsOwed::asking(whatTheyAskedFor())));
    $said = $drawn->said();
    $ready = array_search(__('household.shelf.ready_for_you'), $said, strict: true);

    expect($ready)->toBeInt()
        ->and(array_slice($said, (int) $ready, 4))->toBe([
            __('household.shelf.ready_for_you'),
            sprintf('Dune, %s', WhatMarkupDraws::words(Waiting::Here->saidToTheMember())),
            __(Waiting::Here->saidToTheMember()),
            'Dune',
        ])
        ->and($ready)->toBeLessThan(whereHomeSays($said, WhatMarkupDraws::words('household.title.play')))
        ->and($drawn->offers())->not->toContain(sprintf('Dune, %s', WhatMarkupDraws::words(Waiting::Here->saidToTheMember())));
});

it('draws the hero after their own rows, at the head of the house\'s, where they have titles of their own', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), owing: AMemberWhoIsOwed::asking(whatTheyAskedFor())))->said();
    $hero = whereHomeSays($said, theHeroLabel());

    expect(whereHomeSays($said, WhatMarkupDraws::words('household.shelf.ready_for_you')))->toBeLessThan(whereHomeSays($said, WhatMarkupDraws::words('household.shelf.on_its_way')))
        ->and(whereHomeSays($said, WhatMarkupDraws::words('household.shelf.on_its_way')))->toBeLessThan($hero)
        ->and($hero)->toBeLessThan(whereHomeSays($said, WhatMarkupDraws::words('household.shelf.new')))
        ->and(whereHomeSays($said, WhatMarkupDraws::words('household.shelf.new')))->toBeLessThan(whereHomeSays($said, WhatMarkupDraws::words(Medium::Film->shelvedUnder())));
});

it('draws the hero first on Home where they have no titles of their own', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())))->said();
    // Only the bar's four tabs are said before it: nothing on the screen
    // comes ahead of the hero.
    expect(array_slice($said, 0, whereHomeSays($said, theHeroLabel())))->toBe(array_map(
        static fn(TheHouseholdsTabs $tab): string => WhatMarkupDraws::words($tab->said()),
        TheHouseholdsTabs::cases(),
    ));
});

it('draws nothing of theirs where they asked for nothing', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    expect($screen->theirOwn()->cameBack)->toBeTrue()
        ->and($screen->theirOwn()->rows)->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('household.shelf.ready_for_you'));
});

it('says where their own could not be asked for, while the shelf answered', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), owing: AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->theirOwnWereStopped())->toBeTrue()
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold()))
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->remedyForTheHousehold()))
        ->and($drawn->said())->toContain('A film')
        ->and(array_count_values($drawn->offers())[WhatMarkupDraws::words('household.ask_again')])->toBe(2)
        ->and((string) json_encode(WhatTheDeviceWouldDraw::tree($screen)))->toContain(__('household.ask_again_for_yours'));
});

it('says an obstacle once where both readings met it', function (): void {
    $stopped = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $screen = theShelfScreen(AShelfThatWasRead::met($stopped), owing: AMemberWhoIsOwed::met($stopped));

    expect($screen->theirOwnWereStopped())->toBeFalse()
        ->and(array_count_values(WhatTheDeviceWouldDraw::by($screen)->said())[WhatMarkupDraws::words(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold())])->toBe(1);
});

it('asks for both again when asked to, and once a frame otherwise', function (): void {
    $watching = AShelfThatWasRead::holding(aShelfOfThree());
    $owing = AMemberWhoIsOwed::asking(whatTheyAskedFor());
    $screen = theShelfScreen($watching, owing: $owing);

    $screen->answer();
    $screen->theirOwn();
    $screen->theirOwn();

    expect($watching->askings())->toBe(1)
        ->and($owing->askings())->toBe(1);

    $screen->again();
    $screen->answer();
    $screen->theirOwn();

    expect($watching->askings())->toBe(2)
        ->and($owing->askings())->toBe(2);
});

it('lets go of a session their own requests were refused on', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theShelfScreen(
        AShelfThatWasRead::holding(aShelfOfThree()),
        keychain: $keychain,
        owing: AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
    );

    expect($screen->theirOwn()->cameBack)->toBeFalse()
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAShelfIsReadFrom()->id())->held)->toBeFalse();
});

it('asks nothing of theirs where this device holds no session', function (): void {
    $owing = AMemberWhoIsOwed::asking(whatTheyAskedFor());
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), signedIn: false, owing: $owing);

    expect($screen->theirOwn()->cameBack)->toBeFalse()
        ->and($screen->theirOwn()->wasStopped())->toBeFalse()
        ->and($owing->askings())->toBe(0);
});

it('names each kind against a key rather than in English', function (): void {
    expect(eachPosterIn(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())), 0, static fn(WhatOnePosterSays $poster): string => $poster->keyed['kind']))->toBe([
        'household.medium.film',
        'household.medium.series',
        'household.medium.other',
    ]);
});

it('leaves the year off a holding the core could not date', function (): void {
    // Empty rather than a placeholder: a year invented here would be a fact
    // about somebody's library that nobody claimed.
    expect(eachPosterIn(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())), 0, static fn(WhatOnePosterSays $poster): string => $poster->filling['year']))->toBe(['1999', '', '2012']);
});

it('draws each row under its heading, each poster labelled with its title, kind and year and drawn as its year and kind above its title', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())))->said();
    $first = array_search(__('household.shelf.new'), $drawn, strict: true);

    expect($first)->toBeInt()
        ->and(array_slice($drawn, (int) $first, 10))->toBe([
            __('household.shelf.new'),
            'A film, Film, 1999', '1999 · Film', 'A film',
            'A series, Series', 'Series', 'A series',
            'Something else, Other, 2012', '2012 · Other', 'Something else',
        ]);
});

it('draws a library out of reach as itself, never as an empty shelf', function (): void {
    // The failure this screen is written around. Both are no rows, and one
    // says *you have nothing* while the other says *your collection could not
    // be reached* — and the first, said wrongly, tells somebody their library
    // is gone.
    $outOfReach = theShelfScreen(AShelfThatWasRead::outOfReach(
        Sentences::of(Sentence::of('The media server did not answer.')),
    ));

    expect($outOfReach->answer()->isOutOfReach)->toBeTrue()
        ->and($outOfReach->answer()->cameBack())->toBeFalse()
        ->and($outOfReach->answer()->reasons)->toBe(['The media server did not answer.']);

    $drawn = WhatTheDeviceWouldDraw::by($outOfReach)->said();

    expect($drawn)->toContain(__('household.shelf_is_out_of_reach'));
    expect($drawn)->toContain('The media server did not answer.');
    expect($drawn)->not->toContain(__('household.shelf_is_empty'));
});

it('says an empty shelf in as many words', function (): void {
    $empty = theShelfScreen(AShelfThatWasRead::holdingNothing());

    expect($empty->answer()->cameBack())->toBeTrue()
        ->and($empty->answer()->rows)->toBe([]);

    $drawn = WhatTheDeviceWouldDraw::by($empty)->said();

    expect($drawn)->toContain(__('household.shelf_is_empty'));
    expect($drawn)->not->toContain(__('household.shelf_is_out_of_reach'));
});

it('reports what stood in the way and keeps the way back', function (): void {
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($met->answer()->met)->toEqual(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold())
        ->and($met->answer()->remedy)->toEqual(KindOfObstacle::StackDidNotAnswer->remedyForTheHousehold())
        ->and($met->answer()->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($met)->offers())->not->toBe([]);
});

it('tells a member the house was not reached in the household\'s words, and never where it was tried', function (KindOfObstacle $kind): void {
    // A member has no machine, address or software to look at, so the
    // operator's sentences are not theirs, and the address is never drawn
    // on a member's screen at all.
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::of($kind)->whenTriedAt(theStackAShelfIsReadFrom()->at())));
    $drawn = WhatTheDeviceWouldDraw::by($met)->said();

    expect($drawn)->toContain(__($kind->saidToTheHousehold()))
        ->and($drawn)->toContain(__($kind->remedyForTheHousehold()))
        ->and($drawn)->not->toContain(__($kind->said()))
        ->and(implode("\n", $drawn))->not->toContain('192.168.1.42')
        ->and(implode("\n", $drawn))->not->toContain(__('connection.tried_at', ['address' => '']));
})->with([
    'no answer' => [KindOfObstacle::StackDidNotAnswer],
    'a name found nowhere' => [KindOfObstacle::NameWasNotFound],
    'nothing at the address' => [KindOfObstacle::NothingAtThePairedAddress],
    'a connection turned away' => [KindOfObstacle::ConnectionWasTurnedAway],
]);

it('draws an obstacle as itself, never as a library out of reach', function (): void {
    // The third pair this screen has to keep apart, and the one nothing read.
    // `cameBack()` is already false with a sentence in hand, so whether an
    // obstacle is also out of reach is decided by the template and by nothing
    // else — and an obstacle carrying that flag draws the out-of-reach frame
    // in place of the sentence the core actually sent, with no reason under
    // it at all, because an obstacle carries none.
    //
    // Asked of the frame and not only of the flag: what goes wrong here is
    // what somebody reads, not which boolean is set.
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($met->answer()->isOutOfReach)->toBeFalse()
        ->and($met->answer()->reasons)->toBe([]);

    $drawn = WhatTheDeviceWouldDraw::by($met)->said();

    expect($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->saidToTheHousehold()));
    expect($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->remedyForTheHousehold()));
    expect($drawn)->not->toContain(__('household.shelf_is_out_of_reach'));
    expect($drawn)->not->toContain(__('household.shelf_is_out_of_reach_action'));
});

it('a refused credential is a signed-out device, not a report', function (): void {
    // Asserted of the keychain and not only of the screen. A screen that drew
    // the signed-out frame and left the session in the store is a device that
    // signs itself back in on the next frame, which is the failure the fold
    // lets go of the session inside to prevent.
    $keychain = AKeychainInMemory::working();
    $refused = theShelfScreen(
        AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
        keychain: $keychain,
    );

    expect($refused->answer()->isSignedIn)->toBeFalse()
        ->and($refused->answer()->cameBack())->toBeFalse()
        ->and($refused->answer()->met)->toBe('')
        ->and($refused->answer()->remedy)->toBe('')
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAShelfIsReadFrom()->id())->held)
        ->toBeFalse();
});

it('an obstacle that is not a refused credential leaves the session alone', function (): void {
    $keychain = AKeychainInMemory::working();
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), keychain: $keychain);

    expect($met->answer()->isSignedIn)->toBeTrue()
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAShelfIsReadFrom()->id())->held)
        ->toBeTrue();
});

it('tells a member whose household could not be asked the core\'s own sentence and remedy, as written, and keeps them signed in', function (): void {
    // ADMIT-7: the media server could not vouch for the account. The core
    // wrote the member a sentence and a remedy, and those are what they read,
    // drawn as text even where a line of the core's reads like a key of this
    // app's.
    $keychain = AKeychainInMemory::working();
    $told = WhatTheHouseholdWasTold::said('Your library is not answering right now.', 'household.needs_an_update');
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer)->withWhatTheHouseholdWasTold($told)), keychain: $keychain);
    $drawn = WhatTheDeviceWouldDraw::by($met)->said();

    expect($drawn)->toContain('Your library is not answering right now.')
        ->and($drawn)->toContain('household.needs_an_update')
        ->and($drawn)->not->toContain(__(KindOfObstacle::MediaServerDidNotAnswer->saidToTheHousehold()))
        ->and($drawn)->not->toContain(__(KindOfObstacle::NotForThisAccount->saidToTheHousehold()))
        ->and($met->answer()->isSignedIn)->toBeTrue()
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAShelfIsReadFrom()->id())->held)->toBeTrue();
});

it('reads a route parameter that is not a word as naming no machine', function (): void {
    // The parameter arrives as whatever the router had, which is not
    // necessarily a string. Read as one regardless, a screen would look the
    // stack up under whatever casting produced rather than under nothing.
    $screen = new WhatYouCanWatch(
        AShelfThatWasRead::holding(aShelfOfThree()),
        AMemberWhoIsOwed::owedNothing(),
        AKeychainInMemory::working(),
        AroundThePhone::holding(StacksInMemory::holding(theStackAShelfIsReadFrom())),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => 7]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('carries no sentence and no remedy where the core answered', function (): void {
    // The three arms that are not an obstacle each say nothing stood in the
    // way, and each says it the same way. A constructor that let one of them
    // through with a key in it would put an obstacle's wording on a screen
    // that met none.
    $told = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()))->answer();
    $empty = theShelfScreen(AShelfThatWasRead::holdingNothing())->answer();
    $unread = theShelfScreen(AShelfThatWasRead::outOfReach(
        Sentences::of(Sentence::of('The media server did not answer.')),
    ))->answer();
    $out = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), signedIn: false)->answer();

    foreach ([$told, $empty, $unread, $out] as $answer) {
        expect($answer->met)->toBe('')->and($answer->remedy)->toBe('');
    }

    expect($told->isSignedIn)->toBeTrue()
        ->and($told->isOutOfReach)->toBeFalse()
        ->and($empty->isOutOfReach)->toBeFalse()
        ->and($unread->isSignedIn)->toBeTrue()
        ->and($out->isOutOfReach)->toBeFalse()
        ->and($out->rows)->toBe([])
        ->and($out->reasons)->toBe([]);
});

it('draws the way back in where this device holds no session', function (): void {
    $out = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), signedIn: false);

    expect($out->answer()->isSignedIn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($out)->said())->toContain(__('connection.session_has_ended'));
});

it('asks the machine once per frame, however many fields are read', function (): void {
    $watching = AShelfThatWasRead::holding(aShelfOfThree());
    $screen = theShelfScreen($watching);

    $screen->answer();
    $screen->answer();
    $screen->answer();

    expect($watching->askings())->toBe(1)
        ->and($watching->askedAbout()?->id()->stored())
        ->toBe(theStackAShelfIsReadFrom()->id()->stored());
});

it('asking again is offered, and asks again', function (): void {
    $watching = AShelfThatWasRead::holding(aShelfOfThree());
    $screen = theShelfScreen($watching);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($watching->askings())->toBe(2);
});

it('offers the way to a renewed session', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    expect($screen->signIn())->toBe(AStacksScreen::SignIn->forTheStack(theStackAShelfIsReadFrom()->id()));
});

it('is the member\'s Home tab, titled Home, and offers no way back to the machine', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));
    $tree = WhatTheDeviceWouldDraw::tree($screen);

    expect($screen->itsTab())->toBe(TheHouseholdsTabs::Home)
        ->and(data_get($tree, 'props.nav_title'))->toBe(__('household.tabs.home'))
        ->and((string) json_encode($tree, JSON_UNESCAPED_SLASHES))->not->toContain(sprintf('"%s"', AStacksScreen::Health->forTheStack(theStackAShelfIsReadFrom()->id())));
});

it('is what the router serves under the shelf path', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Shelf->forTheStack(theStackAShelfIsReadFrom()->id()),
    );

    expect($resolved['class'] ?? null)->toBe(WhatYouCanWatch::class);
});

it('offers a member the app\'s settings where the local network was refused, and says so where the phone would not open them', function (): void {
    $settings = AppsSettingsThatOpen::wouldNot();
    $screen = theShelfScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted)), settings: $settings);

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.open_settings'));

    $screen->openTheAppsSettings();

    expect($settings->timesOpened())->toBe(1)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.settings_would_not_open'));
});
