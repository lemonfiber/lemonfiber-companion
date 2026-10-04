<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatIsKeptOfUpdates;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the phone kept of where a stack stands on being up to date, drawn on
// opening for what it is, and nothing on it acted on until the stack answers.
//
// The Updates screen opens on the reading kept from an earlier session, with
// when it was read, before the stack is asked anything; the fresh reading
// replaces it. While the kept one is drawn, taking the update is drawn and
// cannot be used, with the reading's age beside it, and a tap that reaches the
// screen anyway is refused.
//
// Asked of rendered frames, because every one of these is a claim about what
// the glass shows.

/** The moment the screen is opened at. Named for this file (`G10`). */
const WHEN_THE_UPDATES_WERE_OPENED = 1_790_000_000;

/** Two hours: how long before the opening the kept reading was read. */
const TWO_HOURS_BEFORE_THE_UPDATES = 7_200;

/** The machine whose reading was kept. */
function theStackWhoseUpkeepWasKept(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The cellar'),
        Address::of('https://192.168.1.46:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** What the phone kept of the cellar, two hours before it was opened: an update waiting. */
function whatTheCellarKept(): KeepingTheLastUpkeep
{
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals();
    $kept->keeping->keep(
        theStackWhoseUpkeepWasKept()->id(),
        WhatIsKeptOfUpdates::aReadingWithEveryPart(),
        Instant::atEpochSeconds(WHEN_THE_UPDATES_WERE_OPENED - TWO_HOURS_BEFORE_THE_UPDATES),
    );

    return $kept->keeping;
}

/** The cellar's Updates screen, over a stack that answers as a test says, signed in or not. */
function theCellarsUpdates(AStackThatKeepsCurrent $stack, KeepingTheLastUpkeep $kept, bool $signedIn = true): HowCurrentThisStackIs
{
    $cellar = theStackWhoseUpkeepWasKept();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($cellar->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowCurrentThisStackIs(
        $stack,
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($cellar)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(clock: FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_UPDATES_WERE_OPENED))),
        $kept,
    );
    $screen->setParams(['stack' => $cellar->id()->stored()]);

    return $screen;
}

/** The label taking the two waiting services is drawn under. */
function takingTheTwo(): string
{
    return trans_choice('updates.take_them', 2);
}

it('draws the kept reading with its age on the first frame, the update drawn and waiting, and the fresh one replaces it', function (): void {
    $stack = AStackThatKeepsCurrent::withNothingWaiting();
    $screen = theCellarsUpdates($stack, whatTheCellarKept());

    $first = WhatTheDeviceWouldDraw::whileItOpens($screen);

    expect($first->said())->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($first->said())->toContain(trans_choice('updates.behind', 2))
        ->and($first->said())->toContain(__('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($first->offersThatWait())->toBe([takingTheTwo()])
        ->and($stack->askings())->toBe(0);

    $screen->mount();
    $fresh = WhatTheDeviceWouldDraw::by($screen);

    expect($fresh->said())->toContain(__('updates.pins.current'))
        ->and($fresh->said())->not->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($fresh->said())->not->toContain(__('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($fresh->offers())->not->toContain(takingTheTwo())
        ->and($fresh->offersThatWait())->toBe([])
        ->and($stack->askings())->toBe(1);
});

it('offers the update once the stack answers, and takes it when agreed to', function (): void {
    $stack = AStackThatKeepsCurrent::with(WhatIsKeptOfUpdates::aReadingWithEveryPart());
    $screen = theCellarsUpdates($stack, whatTheCellarKept());

    WhatTheDeviceWouldDraw::whileItOpens($screen);
    $screen->mount();
    $fresh = WhatTheDeviceWouldDraw::by($screen);

    expect($fresh->offers())->toContain(takingTheTwo())
        ->and($fresh->offersThatWait())->toBe([])
        ->and($fresh->said())->not->toContain(__('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]));

    $screen->wouldYouLike();

    expect($screen->asking())->toBeInstanceOf(TakingAnUpdate::class);

    $screen->agree();

    expect($stack->taken())->toHaveCount(1);
});

it('keeps the kept reading, waiting, beside what stopped the stack answering, and refuses to take the update', function (): void {
    $stack = AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
    $screen = theCellarsUpdates($stack, whatTheCellarKept());

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($drawn->said())->toContain(__('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($drawn->offersThatWait())->toBe([takingTheTwo()]);

    // The control is the glass's guard; the screen is the other one. A tap
    // that reaches it is refused, and so is an agreement already being asked.
    $screen->wouldYouLike();

    expect($screen->asking())->toBeNull();

    $screen->asking = TakingAnUpdate::offeredBy(WhatIsKeptOfUpdates::aReadingWithEveryPart());
    $asked = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();

    expect($asked->offersThatWait())->toBe([__('health.go_ahead')])
        ->and($asked->said())->toContain(__('connection.usable_once_the_stack_answers', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($stack->taken())->toBe([]);
});

it('keeps the kept reading beside the way back in where the session has ended', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCellarsUpdates(AStackThatKeepsCurrent::withNothingWaiting(), whatTheCellarKept(), signedIn: false));

    expect($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->said())->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($drawn->offersThatWait())->toBe([takingTheTwo()]);
});

it('keeps the fresh reading for the next opening', function (): void {
    $kept = WhatIsKeptOfUpdates::onAPhoneThatSeals()->keeping;
    $screen = theCellarsUpdates(AStackThatKeepsCurrent::with(WhatIsKeptOfUpdates::aReadingWithEveryPart()), $kept);
    $screen->mount();

    $next = WhatTheDeviceWouldDraw::whileItOpens(theCellarsUpdates(AStackThatKeepsCurrent::withNothingWaiting(), $kept));

    expect($next->said())->toContain(__('health.summary.as_of', ['ago' => trans_choice('health.ago.minutes', 0)]))
        ->and($next->offersThatWait())->toBe([takingTheTwo()]);
});

it('opens on the platform\'s indicator, and on nothing kept, where nothing was kept', function (): void {
    $screen = theCellarsUpdates(AStackThatKeepsCurrent::withNothingWaiting(), WhatIsKeptOfUpdates::onAPhoneThatSeals()->keeping);

    expect(WhatTheDeviceWouldDraw::whileItOpens($screen)->said())->toBe([]);
});

it('draws what stopped the reading alone where nothing was kept', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCellarsUpdates(
        AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        WhatIsKeptOfUpdates::onAPhoneThatSeals()->keeping,
    ));

    expect($drawn->said())->toContain(__('connection.no_network'))
        ->and($drawn->said())->not->toContain(trans_choice('updates.behind', 2))
        ->and($drawn->offersThatWait())->toBe([]);
});

/** An update the stack offered, refused when taken because the app may not reach the local network. */
function theCellarRefusingTheUpdate(): AStackThatKeepsCurrent
{
    return AStackThatKeepsCurrent::withButRefusing(WhatIsKeptOfUpdates::aReadingWithEveryPart(), Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted));
}

/** The cellar's screen after an update taken here was refused, with the reading answering as a test says. */
function theCellarsUpdatesAfterARefusedTake(AStackThatKeepsCurrent $reading): HowCurrentThisStackIs
{
    $taken = theCellarsUpdates(theCellarRefusingTheUpdate(), whatTheCellarKept());
    $taken->wouldYouLike();
    $taken->agree();

    $screen = theCellarsUpdates($reading, whatTheCellarKept());
    $screen->lastUpdated = $taken->lastUpdate();

    return $screen;
}

it('draws one control for each action, and asking again once, in every state the screen can be in', function (): void {
    $states = [
        'kept, before the stack is asked' => WhatTheDeviceWouldDraw::whileItOpens(theCellarsUpdates(AStackThatKeepsCurrent::withNothingWaiting(), whatTheCellarKept())),
        'fresh' => WhatTheDeviceWouldDraw::by(theCellarsUpdates(AStackThatKeepsCurrent::with(WhatIsKeptOfUpdates::aReadingWithEveryPart()), whatTheCellarKept())),
        'kept, beside what stopped the reading' => WhatTheDeviceWouldDraw::by(theCellarsUpdates(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), whatTheCellarKept())),
        'kept, beside the way back in' => WhatTheDeviceWouldDraw::by(theCellarsUpdates(AStackThatKeepsCurrent::withNothingWaiting(), whatTheCellarKept(), signedIn: false)),
        'nothing kept, and the stack not read' => WhatTheDeviceWouldDraw::by(theCellarsUpdates(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), WhatIsKeptOfUpdates::onAPhoneThatSeals()->keeping)),
        'fresh, after an update taken here was refused' => WhatTheDeviceWouldDraw::by(theCellarsUpdatesAfterARefusedTake(theCellarRefusingTheUpdate())),
        // The same remedy twice, a switch in the phone's settings, is said once.
        'kept, after an update taken here was refused and the stack not read' => WhatTheDeviceWouldDraw::by(theCellarsUpdatesAfterARefusedTake(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted)))),
    ];

    foreach ($states as $which => $drawn) {
        $offers = $drawn->offers();
        $askingAgain = array_filter($offers, static fn(string $control): bool => $control === __('health.ask_again'));

        expect($offers)->toBe(array_values(array_unique($offers)), $which)
            ->and(count($askingAgain))->toBeLessThanOrEqual(1, $which);
    }
});
