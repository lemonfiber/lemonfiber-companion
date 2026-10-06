<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatAMemberWouldSee;
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
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheKeychainStillHolds;

// The operator's preview of the member's side: the member's Home and Requests,
// drawn from the core's answers for the household's defaults.
//
// Those answers are read for nobody, so the preview names no member and draws
// nothing of the operator's; it asks for nothing, and it always offers the way
// back to the screen it was opened from.

/** The machine the preview is opened on. */
function theStackThePreviewIsOpenedOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('v', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** A shelf of two, as the core answers for the household's defaults. */
function whatTheDefaultsHold(): Shelf
{
    return Shelf::of(
        Holding::of(HoldingId::called('a1'), 'A film', Medium::Film, WhenItCameOut::in(1999)),
        Holding::of(HoldingId::called('b2'), 'A series', Medium::Series, WhenItCameOut::unstated()),
    );
}

/** What the core writes to somebody invited with the household's defaults. */
function whatTheDefaultsAreTold(): Sentences
{
    return Sentences::of(
        Sentence::of('Anything you ask for goes to whoever looks after this house first.'),
        Sentence::of('Your limit: 5 requests a week.'),
    );
}

/** A line of the catalogue, as the screen says it. */
function aLineThePreviewSays(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : $key;
}

/** The preview, over fakes, on the operator's session unless a test says otherwise. */
function thePreview(
    ?AShelfThatWasRead $watching = null,
    ?AMemberWhoIsOwed $owing = null,
    ?Whose $whose = null,
    bool $signedIn = true,
    ?AKeychainInMemory $keychain = null,
): WhatAMemberWouldSee {
    $stack = theStackThePreviewIsOpenedOn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), $whose ?? Whose::theOperator());
    }

    $screen = new WhatAMemberWouldSee(
        $watching ?? AShelfThatWasRead::holding(whatTheDefaultsHold()),
        $owing ?? AMemberWhoIsOwed::owed(whatTheDefaultsAreTold()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the shelf the household\'s defaults hold in a member\'s rows, asked for as the defaults and never as anybody', function (): void {
    $watching = AShelfThatWasRead::holding(whatTheDefaultsHold());
    $drawn = WhatTheDeviceWouldDraw::by(thePreview(watching: $watching))->said();
    $first = array_search(__('household.shelf.new'), $drawn, strict: true);

    expect($first)->toBeInt()
        ->and(array_slice($drawn, (int) $first, 7))->toBe([
            __('household.shelf.new'),
            'A film, Film, 1999', '1999 · Film', 'A film',
            'A series, Series', 'Series', 'A series',
        ])
        ->and($drawn)->toContain(__(Medium::Film->shelvedUnder()))
        ->and($drawn)->toContain(__(Medium::Series->shelvedUnder()))
        ->and($watching->askings())->toBe(1)
        ->and($watching->askingsForTheDefaults())->toBe(1);
});

it('draws what the household\'s defaults are told, asked for as the defaults and never as anybody', function (): void {
    $owing = AMemberWhoIsOwed::owed(whatTheDefaultsAreTold());
    $screen = thePreview(owing: $owing);
    $screen->showRequests();

    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('Your limit: 5 requests a week.')
        ->and($owing->askings())->toBe(1)
        ->and($owing->askingsForTheDefaults())->toBe(1);
});

it('marks every tab of it as a preview, with the way back to the operator\'s screen', function (TheHouseholdsTabs $tab): void {
    $screen = thePreview();
    $tab === TheHouseholdsTabs::Requests ? $screen->showRequests() : $screen->showHome();

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('household.preview.marked'))
        ->and($drawn->offers())->toContain(__('household.preview.back'))
        ->and($screen->tab())->toBe($tab);
})->with([TheHouseholdsTabs::Home, TheHouseholdsTabs::Requests]);

it('goes back to the screen it was opened from, in one step', function (): void {
    $screen = thePreview();
    $screen->showRequests();

    $screen->backToTheSwitchboard();

    expect($screen->getNavigationIntent()?->type)->toBe(NavigationIntent::BACK);
});

it('draws the control a member asks with, and lets nobody use it', function (): void {
    $screen = thePreview();
    $screen->showRequests();

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('household.preview.cannot_ask'))
        ->and($drawn->offersThatWait())->toContain(__('household.preview.ask'));
});

it('names no member and draws none of the operator\'s controls', function (TheHouseholdsTabs $tab): void {
    $screen = thePreview(whose: Whose::theOperator());
    $tab === TheHouseholdsTabs::Requests ? $screen->showRequests() : $screen->showHome();

    $tree = (string) json_encode(WhatTheDeviceWouldDraw::tree($screen), JSON_UNESCAPED_SLASHES);

    // A menu row that shares its word with one of the member's own tabs is
    // the member's word on this screen, so it is left out of the reading.
    $theMembersWords = array_map(static fn(TheHouseholdsTabs $tab): string => aLineThePreviewSays($tab->said()), TheHouseholdsTabs::cases());

    foreach (TheMenu::cases() as $item) {
        if (! in_array(aLineThePreviewSays($item->said()), $theMembersWords, strict: true)) {
            expect($tree)->not->toContain(sprintf('"%s"', aLineThePreviewSays($item->said())));
        }
    }

    foreach (WhereInTheMenu::cases() as $group) {
        expect($tree)->not->toContain(sprintf('"%s"', aLineThePreviewSays($group->said())));
    }

    expect($tree)->not->toContain(__('navigation.menu.switch_stack'))
        ->and($tree)->not->toContain('The loft')
        ->and($tree)->not->toContain('ada');
})->with([TheHouseholdsTabs::Home, TheHouseholdsTabs::Requests]);

it('asks nothing for a member\'s session, and says it is not theirs to ask', function (): void {
    $watching = AShelfThatWasRead::holding(whatTheDefaultsHold());
    $owing = AMemberWhoIsOwed::owed(whatTheDefaultsAreTold());
    $screen = thePreview(watching: $watching, owing: $owing, whose: Whose::member('ada'));

    expect($screen->shelf()->met)->toBe(KindOfObstacle::NotForThisAccount->said())
        ->and($screen->allowance()->met)->toBe(KindOfObstacle::NotForThisAccount->said())
        ->and($watching->askings())->toBe(0)
        ->and($owing->askings())->toBe(0);
});

it('draws the way back in where this phone holds no session', function (): void {
    $screen = thePreview(signedIn: false);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'));

    $screen->showRequests();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($screen->signIn())->toBe(AStacksScreen::SignIn->forTheStack(theStackThePreviewIsOpenedOn()->id()));
});

it('a refused credential is a signed-out device, on either tab', function (): void {
    $keychain = AKeychainInMemory::working();
    $refused = Obstacle::of(KindOfObstacle::CredentialWasRefused);

    thePreview(watching: AShelfThatWasRead::met($refused), keychain: $keychain)->shelf();

    expect(WhatTheKeychainStillHolds::forThe($keychain, theStackThePreviewIsOpenedOn()->id())->held)->toBeFalse();

    $again = AKeychainInMemory::working();
    thePreview(owing: AMemberWhoIsOwed::met($refused), keychain: $again)->allowance();

    expect(WhatTheKeychainStillHolds::forThe($again, theStackThePreviewIsOpenedOn()->id())->held)->toBeFalse();
});

it('draws a library out of reach and a stack that would not say as themselves', function (): void {
    $unread = thePreview(watching: AShelfThatWasRead::outOfReach(Sentences::of(Sentence::of('The media server did not answer.'))));

    expect(WhatTheDeviceWouldDraw::by($unread)->said())->toContain('The media server did not answer.');

    $met = thePreview(owing: AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $met->showRequests();

    expect(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $shelfMet = thePreview(watching: AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect(WhatTheDeviceWouldDraw::by($shelfMet)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->remedy()));
});

it('asks each tab once per frame, and asks both again', function (): void {
    $watching = AShelfThatWasRead::holding(whatTheDefaultsHold());
    $owing = AMemberWhoIsOwed::owed(whatTheDefaultsAreTold());
    $screen = thePreview(watching: $watching, owing: $owing);

    $screen->shelf();
    $screen->shelf();
    $screen->allowance();
    $screen->allowance();
    $screen->again();
    $screen->shelf();
    $screen->allowance();

    expect($watching->askings())->toBe(2)
        ->and($owing->askings())->toBe(2);
});

it('opens on Home, and reads a word it does not know as Home', function (): void {
    $screen = thePreview();

    expect($screen->tab())->toBe(TheHouseholdsTabs::Home);

    $screen->showing = 'profile';

    expect($screen->tab())->toBe(TheHouseholdsTabs::Home);
});

it('is opened from the operator\'s menu, in the household\'s group, and served under its path', function (): void {
    $path = TheMenu::ViewAsMember->screen()->forTheStack(theStackThePreviewIsOpenedOn()->id());

    expect(TheMenu::ViewAsMember->group())->toBe(WhereInTheMenu::Household)
        ->and(NativeRouter::resolve($path)['class'] ?? null)->toBe(WhatAMemberWouldSee::class);
});
