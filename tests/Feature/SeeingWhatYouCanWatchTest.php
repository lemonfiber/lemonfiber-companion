<?php

declare(strict_types=1);

use Modules\Household\Internal\Presenters\HowAShelfReads;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Household\Internal\ViewModels\WhatOneHoldingSays;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\StacksInMemory;
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

    $screen = new WhatYouCanWatch($watching, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain), $settings ?? new AppsSettingsThatOpen());
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

/**
 * What one row of the shelf screen carries, as one thing read off each poster.
 *
 * @param Closure(WhatOneHoldingSays): string $read
 *
 * @return list<string>
 */
function eachPosterIn(WhatYouCanWatch $screen, int $row, Closure $read): array
{
    return array_map($read, $screen->answer()->rows[$row]->holdings);
}

/** A poster's title. */
function itsTitle(): Closure
{
    return static fn(WhatOneHoldingSays $poster): string => $poster->titled;
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

it('offers no poster as a control while there is nothing to press on one', function (): void {
    $screen = WhatTheDeviceWouldDraw::by(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())));

    expect($screen->said())->toContain('A film, Film, 1999')
        ->and($screen->offers())->toBe([__('household.ask_again')]);
});

it('names each kind against a key rather than in English', function (): void {
    expect(eachPosterIn(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())), 0, static fn(WhatOneHoldingSays $poster): string => $poster->medium))->toBe([
        'household.medium.film',
        'household.medium.series',
        'household.medium.other',
    ]);
});

it('leaves the year off a holding the core could not date', function (): void {
    // Empty rather than a placeholder: a year invented here would be a fact
    // about somebody's library that nobody claimed.
    expect(eachPosterIn(theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree())), 0, static fn(WhatOneHoldingSays $poster): string => $poster->year))->toBe(['1999', '', '2012']);
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

    expect($met->answer()->met)->toEqual(KindOfObstacle::StackDidNotAnswer->said())
        ->and($met->answer()->remedy)->toEqual(KindOfObstacle::StackDidNotAnswer->remedy())
        ->and($met->answer()->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($met)->offers())->not->toBe([]);
});

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

    expect($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
    expect($drawn)->toContain(__(KindOfObstacle::StackDidNotAnswer->remedy()));
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

it('reads a route parameter that is not a word as naming no machine', function (): void {
    // The parameter arrives as whatever the router had, which is not
    // necessarily a string. Read as one regardless, a screen would look the
    // stack up under whatever casting produced rather than under nothing.
    $screen = new WhatYouCanWatch(
        AShelfThatWasRead::holding(aShelfOfThree()),
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
