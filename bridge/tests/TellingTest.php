<?php

declare(strict_types=1);

use Lemonfiber\Native\HowOften;
use Lemonfiber\Native\Repeat;
use Lemonfiber\Native\Telling;
use Lemonfiber\Native\Told;
use Lemonfiber\Native\WhatTheOperatorSaid;
use Lemonfiber\Native\WhyNothingWasTold;
use Native\Mobile\Testing\FakeBridge;

// The notification capability's PHP face, driven through the real bridge call.
//
// `FakeBridge` is `nativephp/mobile`'s own seam: it binds into the container
// and intercepts `nativephp_call()` in-process. Using it rather than an
// interface of our own means every assertion here goes through the method name,
// the JSON out and the decoding of the answer — the whole path — instead of
// through something built to resemble it.
//
// The bridge names are written out as literals, deliberately. `Telling` reaches
// them through `Call`, so a test spelling them `Call::Show->value` would agree
// with a wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json` separately, which is the pair that keeps the Kotlin and the
// Swift in the conversation.
//
// What the native halves decide is not re-litigated here. `NotificationRule`
// and `Recurrence` carry that, in Kotlin and in Swift, with the same cases
// each. This file is about what the PHP can get wrong on its own: which
// function it calls, what it sends, and what it does with an answer it cannot
// read.

beforeEach(function (): void {
    FakeBridge::disable();
});

/**
 * Why the centre did not do what it was asked, or nothing where it did.
 *
 * A reading of the sum type rather than a second way of asking it. `Told` has
 * no `wasTold()` beside `either()` on purpose — a check-then-get pair is an
 * invitation to call the getter without the check — so a suite that wants a
 * plain value builds one here, in one place, out of the arms.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of the same name are a fatal the moment both load (`G10`).
 */
function whyTheCentreDidNot(Told $told): ?WhyNothingWasTold
{
    $answered = $told->either(
        done: static fn(): WhatTheOperatorSaid => WhatTheOperatorSaid::Granted,
        withheld: static fn(WhyNothingWasTold $why): WhyNothingWasTold => $why,
    );

    return $answered instanceof WhyNothingWasTold ? $answered : null;
}

it('reads the standing answer without asking for one', function (): void {
    // The whole reason reading and asking are two functions. A test that let
    // them be one would pass against an implementation that prompted somebody
    // in order to report what they had already said.
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.Standing', ['outcome' => 'denied']);

    expect(new Telling()->standing())->toBe(WhatTheOperatorSaid::Denied);

    $bridge->assertCalled('Lemonfiber.Telling.Standing')
        ->assertNotCalled('Lemonfiber.Telling.Ask');
});

it('reads each of the three answers a permission can have', function (): void {
    foreach ([
        'granted' => WhatTheOperatorSaid::Granted,
        'denied' => WhatTheOperatorSaid::Denied,
        'not_determined' => WhatTheOperatorSaid::NotDetermined,
    ] as $word => $meant) {
        FakeBridge::disable();
        FakeBridge::enable()->respondTo('Lemonfiber.Telling.Standing', ['outcome' => $word]);

        expect(new Telling()->standing())->toBe($meant, $word);
    }
});

it('reads a word it does not know as nobody having been asked', function (): void {
    // The safe answer in both directions: nothing is shown, and no refusal is
    // recorded that nobody made. A `from()` here would raise on a launch screen
    // the first time the bridge grew a word.
    FakeBridge::enable()->respondTo('Lemonfiber.Telling.Standing', ['outcome' => 'provisionally_maybe']);

    expect(new Telling()->standing())->toBe(WhatTheOperatorSaid::NotDetermined);
});

it('reads no answer at all as nobody having been asked', function (): void {
    FakeBridge::enable();

    expect(new Telling()->standing())->toBe(WhatTheOperatorSaid::NotDetermined);
});

it('answers what the operator said to the prompt', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.Ask', ['outcome' => 'granted']);

    expect(new Telling()->ask())->toBe(WhatTheOperatorSaid::Granted);

    $bridge->assertCalled('Lemonfiber.Telling.Ask');
});

it('sends what a notification says and nothing else', function (): void {
    // Three fields. Every one the vendor's own carries beyond these — a sound,
    // a badge, an arbitrary data payload, up to three action buttons — is
    // another place a value could travel that this application never meant to
    // send, and none of them has a caller here.
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.Show', ['outcome' => 'shown']);

    expect(whyTheCentreDidNot(new Telling()->show('lemonfiber.backup.finished', 'The loft', 'A backup finished')))
        ->toBeNull();

    $bridge->assertCalled(
        'Lemonfiber.Telling.Show',
        fn(array $sent): bool => $sent === [
            'id' => 'lemonfiber.backup.finished',
            'title' => 'The loft',
            'body' => 'A backup finished',
        ],
    );
});

it('reads each refusal the centre can answer with', function (): void {
    // Every word both native halves can answer with, not only the ones `Show`
    // can. Two of these reach a caller from scheduling alone, and leaving them
    // out is what a handset found: an unrecognised word falls to *the device
    // refused*, so a repeat the bridge deliberately declined came back as the
    // device having failed.
    foreach ([
        'not_permitted' => WhyNothingWasTold::NotPermitted,
        'no_such_channel' => WhyNothingWasTold::NoSuchChannel,
        'the_time_has_passed' => WhyNothingWasTold::TheTimeHasPassed,
        'no_such_repeat' => WhyNothingWasTold::NoSuchRepeat,
        'the_device_refused' => WhyNothingWasTold::TheDeviceRefused,
    ] as $word => $meant) {
        FakeBridge::disable();
        FakeBridge::enable()->respondTo(
            'Lemonfiber.Telling.Show',
            ['outcome' => 'withheld', 'because' => $word],
        );

        expect(whyTheCentreDidNot(new Telling()->show('a', 'b', 'c')))->toBe($meant, $word);
    }
});

it('reads a refusal it cannot explain as the device having refused', function (): void {
    // Not as a permission, which is the tempting default and the wrong one: it
    // would put a screen in front of somebody offering to ask for something no
    // dialog on that machine could grant.
    FakeBridge::enable()->respondTo('Lemonfiber.Telling.Show', ['outcome' => 'withheld']);

    expect(whyTheCentreDidNot(new Telling()->show('a', 'b', 'c')))
        ->toBe(WhyNothingWasTold::TheDeviceRefused);
});

it('reads no answer at all as the device having refused', function (): void {
    FakeBridge::enable();

    expect(whyTheCentreDidNot(new Telling()->show('a', 'b', 'c')))
        ->toBe(WhyNothingWasTold::TheDeviceRefused);
});

it('sends the moment a scheduled notification is wanted at', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.Schedule', ['outcome' => 'scheduled']);

    expect(whyTheCentreDidNot(new Telling()->schedule('a', 'b', 'c', 1_800_000_000)))
        ->toBeNull();

    $bridge->assertCalled(
        'Lemonfiber.Telling.Schedule',
        fn(array $sent): bool => $sent['at'] === 1_800_000_000,
    );
});

it('sends every field a repeat is described by', function (): void {
    // Six fields that are all integers except one, which is what makes a value
    // object worth having: the call that puts `weekday` where `dayOfMonth` was
    // meant compiles, and produces an alert on a day nobody chose.
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.ScheduleRecurring', ['outcome' => 'scheduled']);

    expect(whyTheCentreDidNot(new Telling()->scheduleRecurring('a', 'b', 'c', Repeat::weekly(0, 9, 30))))
        ->toBeNull();

    $bridge->assertCalled(
        'Lemonfiber.Telling.ScheduleRecurring',
        fn(array $sent): bool => $sent === [
            'id' => 'a',
            'title' => 'b',
            'body' => 'c',
            'frequency' => 'weekly',
            'hour' => 9,
            'minute' => 30,
            'weekday' => 0,
            'dayOfMonth' => 1,
            'month' => 1,
        ],
    );
});

it('describes each of the five ways a notification can repeat', function (): void {
    expect(Repeat::hourly(15)->asAsked()['frequency'])->toBe(HowOften::Hourly->value)
        ->and(Repeat::daily(9, 0)->asAsked()['frequency'])->toBe(HowOften::Daily->value)
        ->and(Repeat::weekly(3, 9, 0)->asAsked()['weekday'])->toBe(3)
        ->and(Repeat::monthly(12, 9, 0)->asAsked()['dayOfMonth'])->toBe(12)
        ->and(Repeat::yearly(6, 12, 9, 0)->asAsked()['month'])->toBe(6);
});

it('takes one notification back by name', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Telling.Cancel', ['outcome' => 'cancelled']);

    expect(whyTheCentreDidNot(new Telling()->cancel('lemonfiber.backup.finished')))
        ->toBeNull();

    $bridge->assertCalled(
        'Lemonfiber.Telling.Cancel',
        fn(array $sent): bool => $sent === ['id' => 'lemonfiber.backup.finished'],
    );
});

it('takes every notification back at once', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Telling.CancelAll', ['outcome' => 'cancelled']);

    expect(whyTheCentreDidNot(new Telling()->cancelAll()))
        ->toBeNull();
});

it('clears the count on the icon', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Telling.ClearBadge', ['outcome' => 'cleared']);

    expect(whyTheCentreDidNot(new Telling()->clearBadge()))
        ->toBeNull();
});

it('reads what is still to come', function (): void {
    FakeBridge::enable()->respondTo(
        'Lemonfiber.Telling.Pending',
        ['outcome' => 'read', 'pending' => ['one', 'two']],
    );

    expect(new Telling()->pending())->toBe(['one', 'two']);
});

it('tells nothing pending apart from nobody answering', function (): void {
    // The distinction a list alone cannot carry. An empty list says nothing is
    // scheduled; null says this process cannot ask, which is every machine that
    // is not a handset.
    FakeBridge::enable()->respondTo('Lemonfiber.Telling.Pending', ['outcome' => 'read', 'pending' => []]);

    expect(new Telling()->pending())->toBe([]);

    FakeBridge::disable();
    FakeBridge::enable();

    expect(new Telling()->pending())->toBeNull();
});

it('drops anything in a pending list that is not a name', function (): void {
    // A bridge that grew a richer entry would otherwise reach a caller expecting
    // strings, which is a type error on a handset and nowhere else.
    FakeBridge::enable()->respondTo(
        'Lemonfiber.Telling.Pending',
        ['outcome' => 'read', 'pending' => ['one', 17, ['two']]],
    );

    expect(new Telling()->pending())->toBe(['one']);
});
