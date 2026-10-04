<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatTheHouseholdAsked;
use Modules\Operator\Internal\ViewModels\WhatOneRequestSays;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AHouseholdThatAsked;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatIsKeptOfRequests;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the phone kept of what a household asked for, drawn on opening for what
// it is, and nothing on it decided until the stack answers.
//
// The Requests screen opens on the reading kept from an earlier session, with
// when it was read, before the stack is asked anything; the fresh reading
// replaces it. While the kept one is drawn, approving and turning down are
// drawn on every row that waits and cannot be used, with the reading's age
// beside them, and a tap that reaches the screen anyway is refused.
//
// Asked of rendered frames, because every one of these is a claim about what
// the glass shows.

/** The moment the screen is opened at. Named for this file (`G10`). */
const WHEN_THE_REQUESTS_WERE_OPENED = 1_790_000_000;

/** Two hours: how long before the opening the kept reading was read. */
const TWO_HOURS_BEFORE_THE_REQUESTS = 7_200;

/** The machine whose household's reading was kept. */
function theStackWhoseRequestsWereKept(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The garden room'),
        Address::of('https://192.168.1.48:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** What the phone kept of the garden room's household, read two hours before it was opened: every part a reading can have. */
function whatTheGardenRoomKept(): KeepingWhatWasAsked
{
    $kept = WhatIsKeptOfRequests::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_OPENED - TWO_HOURS_BEFORE_THE_REQUESTS));
    $kept->keeping->keep(theStackWhoseRequestsWereKept()->id(), WhatIsKeptOfRequests::aReadingWithEveryPart());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_OPENED));

    return $kept->keeping;
}

/** Nothing kept of the garden room's household, on a phone whose clock reads the opening. */
function nothingKeptOfTheGardenRoom(): KeepingWhatWasAsked
{
    return WhatIsKeptOfRequests::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_OPENED))->keeping;
}

/** The garden room's Requests screen, over a household that answers as a test says, signed in or not. */
function theGardenRoomsRequests(AHouseholdThatAsked $wanting, KeepingWhatWasAsked $kept, bool $signedIn = true): WhatTheHouseholdAsked
{
    $room = theStackWhoseRequestsWereKept();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($room->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatTheHouseholdAsked(
        $wanting,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($room)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $kept,
    );
    $screen->setParams(['stack' => $room->id()->stored()]);

    return $screen;
}

/** The household out of reach: the phone has no network. */
function theGardenRoomOutOfReach(): AHouseholdThatAsked
{
    return AHouseholdThatAsked::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
}

/** What the household asks for when it answers: one request the kept reading never named, waiting on a yes. */
function theGardenRoomAnswering(): AHouseholdThatAsked
{
    return AHouseholdThatAsked::wanting(Requested::of(
        Wanted::of(9, 'Robin', 'Arrival', Size::measured(3_000_000_000), HowARequestStands::said(Waiting::ForApproval)),
    ));
}

/**
 * What a key says in the catalogue, as a screen draws it.
 *
 * @param array<string, string> $replace
 */
function asTheGardenRoomSaysIt(string $key, array $replace = []): string
{
    $said = __($key, $replace);

    return is_string($said) ? $said : $key;
}

/** How old the kept reading is, as the screen says it. */
function readTwoHoursBeforeTheRequests(): string
{
    return asTheGardenRoomSaysIt('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]);
}

/** Why a decision waits, as the screen says it. */
function decidableOnceTheGardenRoomAnswers(): string
{
    return asTheGardenRoomSaysIt('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]);
}

/**
 * The two decisions drawn on the one row waiting on a yes, as the controls say them.
 *
 * @return list<string>
 */
function theDecisionsOnTheRowThatWaits(): array
{
    return [asTheGardenRoomSaysIt('household.approve'), asTheGardenRoomSaysIt('household.turn_down')];
}

it('draws the kept reading with its age on the first frame, and the fresh one replaces it', function (): void {
    $wanting = theGardenRoomAnswering();
    $screen = theGardenRoomsRequests($wanting, whatTheGardenRoomKept());

    $first = WhatTheDeviceWouldDraw::whileItOpens($screen);

    expect($first->said())->toContain(readTwoHoursBeforeTheRequests())
        ->and($first->said())->toContain('Dune')
        ->and($first->said())->toContain(decidableOnceTheGardenRoomAnswers())
        ->and($first->offersThatWait())->toBe(theDecisionsOnTheRowThatWaits())
        ->and($wanting->askings())->toBe(0);

    $screen->mount();
    $fresh = WhatTheDeviceWouldDraw::by($screen);

    expect($fresh->said())->toContain('Arrival')
        ->and($fresh->said())->not->toContain('Dune')
        ->and($fresh->said())->not->toContain(readTwoHoursBeforeTheRequests())
        ->and($fresh->said())->not->toContain(decidableOnceTheGardenRoomAnswers())
        ->and($fresh->offersThatWait())->toBe([])
        ->and($wanting->askings())->toBe(1);
});

it('keeps the fresh reading for the next opening', function (): void {
    $kept = nothingKeptOfTheGardenRoom();
    $screen = theGardenRoomsRequests(theGardenRoomAnswering(), $kept);
    $screen->mount();
    WhatTheDeviceWouldDraw::by($screen);

    $next = WhatTheDeviceWouldDraw::whileItOpens(theGardenRoomsRequests(theGardenRoomOutOfReach(), $kept));

    expect($next->said())->toContain(asTheGardenRoomSaysIt('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($next->said())->toContain('Arrival')
        ->and($next->offersThatWait())->toBe(theDecisionsOnTheRowThatWaits());
});

it('keeps the kept reading, waiting, beside what stopped the stack answering, and refuses to decide on it', function (): void {
    $wanting = theGardenRoomOutOfReach();
    $screen = theGardenRoomsRequests($wanting, whatTheGardenRoomKept());

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->toContain(readTwoHoursBeforeTheRequests())
        ->and($drawn->said())->toContain(decidableOnceTheGardenRoomAnswers())
        ->and($drawn->offersThatWait())->toBe(theDecisionsOnTheRowThatWaits());

    // The control is the glass's guard; the screen is the other one. A tap
    // that reaches it is refused, and so is a reason already being written.
    $screen->approve('1');
    $screen->wouldDecline('1');

    expect($screen->turningDown())->toBeNull();

    $screen->turningDown = new WhatOneRequestSays(1, 'Dune', 'Mira', 'household.waiting', wantsADecision: true, sizeSaid: 'household.size_unknown', sizeFigure: 0, sizeUnit: '');
    $screen->because = 'We have it already';
    $writing = WhatTheDeviceWouldDraw::by($screen);
    $screen->decline();

    expect($writing->offersThatWait())->toBe([asTheGardenRoomSaysIt('household.turn_it_down')])
        ->and($writing->said())->toContain(decidableOnceTheGardenRoomAnswers())
        ->and($wanting->whatItWasToldWasDecided())->toBe([]);
});

it('keeps the kept reading beside the way back in where the session has ended', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomAnswering(), whatTheGardenRoomKept(), signedIn: false));

    expect($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->said())->toContain(readTwoHoursBeforeTheRequests())
        ->and($drawn->offersThatWait())->toBe(theDecisionsOnTheRowThatWaits());
});

it('opens on the platform\'s indicator where nothing was kept, and draws what stopped the reading alone', function (): void {
    expect(WhatTheDeviceWouldDraw::whileItOpens(theGardenRoomsRequests(theGardenRoomAnswering(), nothingKeptOfTheGardenRoom()))->said())->toBe([]);

    $drawn = WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomOutOfReach(), nothingKeptOfTheGardenRoom()));

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->not->toContain('Dune')
        ->and($drawn->offersThatWait())->toBe([]);
});

it('offers the decisions once the stack answers, and sends one made', function (): void {
    $wanting = theGardenRoomAnswering();
    $screen = theGardenRoomsRequests($wanting, whatTheGardenRoomKept());

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->toContain(theDecisionsOnTheRowThatWaits()[0])
        ->and($drawn->offersThatWait())->toBe([]);

    $screen->approve('9');

    expect($wanting->whatItWasToldWasDecided())->toHaveCount(1);
});

it('draws one control for each action, and asking again once, in every state the screen can be in', function (): void {
    $states = [
        'kept, before the stack is asked' => WhatTheDeviceWouldDraw::whileItOpens(theGardenRoomsRequests(theGardenRoomAnswering(), whatTheGardenRoomKept())),
        'fresh' => WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomAnswering(), whatTheGardenRoomKept())),
        'kept, beside what stopped the reading' => WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomOutOfReach(), whatTheGardenRoomKept())),
        'kept, beside the way back in' => WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomAnswering(), whatTheGardenRoomKept(), signedIn: false)),
        'nothing kept, and the stack not read' => WhatTheDeviceWouldDraw::by(theGardenRoomsRequests(theGardenRoomOutOfReach(), nothingKeptOfTheGardenRoom())),
    ];

    foreach ($states as $which => $drawn) {
        $offers = $drawn->offers();
        $askingAgain = array_filter($offers, static fn(string $control): bool => $control === __('health.ask_again'));

        expect($offers)->toBe(array_values(array_unique($offers)), $which)
            ->and(count($askingAgain))->toBeLessThanOrEqual(1, $which);
    }
});
