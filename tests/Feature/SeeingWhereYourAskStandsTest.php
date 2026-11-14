<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhereYourAskStands;
use Modules\Household\Internal\ViewModels\ALineAsShown;
use Modules\Household\Internal\WhereAnAskIsKept;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfRequest;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheKeychainStillHolds;

function theHouseAnAskIsIn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

function whatTheyAskedOfMusicAndBooks(): Requested
{
    return Requested::of(
        Wanted::of(RequestId::named('music-3'), 'Robin', 'Rumours', KindOfRequest::Album, Size::unknown(), HowARequestStands::said(Waiting::Here)),
        Wanted::of(RequestId::named('books-1'), 'Robin', 'Discworld', KindOfRequest::BookSeries, Size::unknown(), HowARequestStands::said(Waiting::PartlyHere)),
        Wanted::of(RequestId::named('music-4'), 'Robin', 'Tusk', KindOfRequest::Artist, Size::unknown(), HowARequestStands::said(Waiting::Getting)),
        Wanted::notYetNamed(RequestId::named('books-2'), 'Robin', KindOfRequest::Author, Size::unknown(), HowARequestStands::unnamed()),
    );
}

function thePageOfAnAsk(AMemberWhoIsOwed $owing, string $reference, ?AKeychainInMemory $keychain = null, bool $signedIn = true): WhereYourAskStands
{
    $stack = theHouseAnAskIsIn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhereYourAskStands($owing, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain));
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $reference]);

    return $screen;
}

it('draws an arrived album with its kind, here as the action not usable, and where in the house to find it', function (): void {
    $screen = thePageOfAnAsk(AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks()), 'music-3');
    $ask = $screen->ask()->ask;

    expect($screen->ask()->cameBack)->toBeTrue()
        ->and($ask?->title)->toBe('Rumours')
        ->and($ask?->kind)->toBe('household.request_kind.album')
        ->and($ask?->standing)->toBe('household.asked.here')
        ->and($ask?->foundIn)->toBe(WhereAnAskIsKept::IN_THE_MUSIC)
        ->and($ask?->poster->titled)->toBe('Rumours')
        ->and($ask?->poster->plays)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('Rumours', __('household.request_kind.album'), __('household.asked.here'), __(WhereAnAskIsKept::IN_THE_MUSIC))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('household.title.play'));
});

it('says a book series held in part is in the house\'s books', function (): void {
    $ask = thePageOfAnAsk(AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks()), 'books-1')->ask()->ask;

    expect($ask?->standing)->toBe('household.asked.partly-here')
        ->and($ask?->foundIn)->toBe(WhereAnAskIsKept::IN_THE_BOOKS)
        ->and($ask?->saysWhereToFindIt())->toBeTrue();
});

it('says nothing of where to find an ask that has not arrived, and names one no service named by its kind', function (string $reference, string|ALineAsShown $title, string $standing): void {
    $ask = thePageOfAnAsk(AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks()), $reference)->ask()->ask;

    expect($ask?->title)->toEqual($title)
        ->and($ask?->standing)->toBe($standing)
        ->and($ask?->foundIn)->toBe('')
        ->and($ask?->saysWhereToFindIt())->toBeFalse();
})->with([
    'on its way' => ['music-4', 'Tusk', 'household.asked.getting'],
    'unnamed and not stood anywhere the core named' => ['books-2', new ALineAsShown('household.not_yet_named.theirs.author', []), 'household.asked.unnamed'],
]);

it('says an ask is not among what they asked for where no reference matches, or none was given', function (string $reference): void {
    $screen = thePageOfAnAsk(AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks()), $reference);

    expect($screen->ask()->cameBack)->toBeTrue()
        ->and($screen->ask()->ask)->toBeNull()
        ->and($screen->ask()->stopped)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.your_ask.not_among'));
})->with(['no such ask' => ['music-99'], 'nothing named' => [' ']]);

it('says what stood in the way, and keeps the session for anything but a refused credential', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePageOfAnAsk(AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), 'music-3', $keychain);

    expect($screen->ask()->cameBack)->toBeFalse()
        ->and($screen->ask()->stopped)->not->toBeNull()
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theHouseAnAskIsIn()->id())->held)->toBeTrue();
});

it('lets the session go where the credential is refused', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePageOfAnAsk(AMemberWhoIsOwed::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), 'music-3', $keychain);

    $screen->ask();

    expect(WhatTheKeychainStillHolds::forThe($keychain, theHouseAnAskIsIn()->id())->held)->toBeFalse();
});

it('asks nothing where this device holds no session, and offers signing in', function (): void {
    $owing = AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks());
    $screen = thePageOfAnAsk($owing, 'music-3', signedIn: false);

    expect($screen->ask()->cameBack)->toBeFalse()
        ->and($screen->ask()->stopped)->toBeNull()
        ->and($owing->askedAbout())->toBeNull()
        ->and($screen->signIn())->toBe(AStacksScreen::SignIn->forTheStack(theHouseAnAskIsIn()->id()));
});

it('asks once per frame, and again when told to', function (): void {
    $owing = AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks());
    $screen = thePageOfAnAsk($owing, 'music-3');

    $screen->ask();
    $screen->ask();
    $screen->again();
    $screen->ask();

    expect($owing->askings())->toBe(2);
});

it('is under Home, with a way back, and registered under the route its poster names', function (): void {
    $screen = thePageOfAnAsk(AMemberWhoIsOwed::asking(whatTheyAskedOfMusicAndBooks()), 'music-3');
    $resolved = NativeRouter::resolve(AStacksScreen::Asked->forTheStacks(theHouseAnAskIsIn()->id(), RequestId::named('music-3')));

    expect($screen->itsTab())->toBe(TheHouseholdsTabs::Home)
        ->and($resolved['class'] ?? null)->toBe(WhereYourAskStands::class)
        ->and(AStacksScreen::Asked->forTheStacks(theHouseAnAskIsIn()->id(), RequestId::named('a b')))->toEndWith('/asked/a%20b');
});
