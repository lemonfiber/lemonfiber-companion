<?php

declare(strict_types=1);

use Lemonfiber\Native\Telling;
use Lemonfiber\Native\WhyNothingWasTold;
use Modules\Device\Api\PlatformNotifier;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhyNothingIsShown;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ANotificationCentreOnAHandset;
use Tests\Support\Fakes\ANotifierInMemory;

// The Notifier contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every other test that needs "the operator was told" will hand
// its subject an `ANotifierInMemory` and never see a notification centre, so a
// fake easier to satisfy than the platform would make the refusal green
// against a centre that always says yes — and the refusal is the one somebody
// who already declined depends on.
//
// What is asserted is only what both must promise. The adapter composes words
// from the catalogue and the fake records an arm, so "the title reads like
// this" belongs to the adapter's own assertions further down — a contract
// asserting it would either fail on the fake or be weakened to pass, and a
// weakened contract is how a fake drifts.

/**
 * Which arm answered, as a word.
 *
 * Named for this file rather than `fold`: the root suites share one namespace,
 * and two functions of the same name are a fatal the moment both load (`G10`).
 */
function whatBecameOfIt(Shown $shown): string
{
    return $shown->either(
        delivered: fn(): Code => Code::of('delivered'),
        withheld: fn(WhyNothingIsShown $why): Code => Code::of($why->name),
    )->shown();
}

function somethingWorthSaying(): Notification
{
    return Notification::fromTheCore(StackId::rememberedAs('the-loft'), WhatTheCoreDecided::toSay('backup.finished'));
}

/**
 * The adapter, over a centre scripted into the real bridge.
 *
 * `FakeBridge` is `nativephp/mobile`'s own seam: it intercepts
 * `nativephp_call()` in-process, so this arm of the contract runs the function
 * names from the manifest, the JSON out and the decoding of the answer —
 * the whole path — rather than something built to resemble it.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 */
function overAHandset(ANotificationCentreOnAHandset $centre): PlatformNotifier
{
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Telling.Standing', $centre->standing(...))
        ->respondTo('Lemonfiber.Telling.Ask', $centre->ask(...))
        ->respondTo('Lemonfiber.Telling.Show', $centre->show(...));

    return new PlatformNotifier(new Telling(), Catalogue::words());
}

/**
 * Every implementation of the port, in each of the three standings.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed back
 * as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * Three standings rather than two. `NotYet` and `Declined` are the same to a
 * caller asking whether it may show something and opposite to one deciding
 * whether to ask, which is the distinction this whole change is about — a suite
 * that tested "allowed" and "not allowed" could not see it.
 *
 * @return array<string, Closure(): Notifier>
 */
function everyNotifier(Asked $standing): array
{
    return [
        'the fake' => fn(): Notifier => match ($standing) {
            Asked::Granted => ANotifierInMemory::allowed(),
            Asked::Declined => ANotifierInMemory::refused(),
            Asked::NotYet => ANotifierInMemory::unasked(),
        },
        'the adapter' => fn(): Notifier => overAHandset(match ($standing) {
            Asked::Granted => ANotificationCentreOnAHandset::allowed(),
            Asked::Declined => ANotificationCentreOnAHandset::refused(),
            Asked::NotYet => ANotificationCentreOnAHandset::unasked(),
        }),
    ];
}

it('reads what the operator has already said', function (): void {
    foreach (Asked::cases() as $standing) {
        foreach (everyNotifier($standing) as $which => $make) {
            expect($make()->standing())->toBe($standing, $which);
        }
    }
});

it('N4-R4 — asking is separate from reading, so reading never prompts', function (): void {
    // The defect this whole change is about, asserted rather than described.
    // `isPermitted()` asked in order to answer, and `show()` called it — so a
    // notification arriving re-prompted somebody who had already declined.
    //
    // Counted on the adapter, because the count is what is being promised:
    // not asking again is not a question about a return value, it is a question
    // about how many times somebody was interrupted.
    $centre = ANotificationCentreOnAHandset::refused();
    $notifier = overAHandset($centre);

    $notifier->standing();
    $notifier->standing();
    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    expect($centre->prompts())->toBe(0);
});

it('N4-R4 — a declined permission is not asked for again', function (): void {
    foreach (everyNotifier(Asked::Declined) as $which => $make) {
        $notifier = $make();

        expect($notifier->ask())->toBe(Asked::Declined, $which)
            ->and($notifier->standing())->toBe(Asked::Declined, $which);
    }
});

it('N4-R4 — the prompt is raised once, and not again once answered', function (): void {
    $centre = ANotificationCentreOnAHandset::unasked();
    $notifier = overAHandset($centre);

    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($centre->prompts())->toBe(1);

    // The second ask finds a standing answer and returns it without asking.
    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($centre->prompts())->toBe(1);
});

it('N4-R1 — asks where nothing has been asked yet', function (): void {
    foreach (everyNotifier(Asked::NotYet) as $which => $make) {
        expect($make()->ask())->toBe(Asked::Granted, $which);
    }
});

it('shows nothing where the operator has not allowed it', function (): void {
    foreach ([Asked::Declined, Asked::NotYet] as $standing) {
        foreach (everyNotifier($standing) as $which => $make) {
            // Withheld rather than thrown, and the reason is one a screen can
            // act on: `mightBeWorthAsking()` separates somebody who has not been
            // asked from somebody who said no.
            expect(whatBecameOfIt($make()->show(somethingWorthSaying())))
                ->toBe(WhyNothingIsShown::NotificationsAreNotPermitted->name, $which);
        }
    }
});

it('shows something where the operator has allowed it', function (): void {
    foreach (everyNotifier(Asked::Granted) as $which => $make) {
        expect(whatBecameOfIt($make()->show(somethingWorthSaying())))->toBe('delivered', $which);
    }
});

it('shows the guarded form without complaint', function (): void {
    // Both arms have to survive both implementations. The locked form is the one
    // a fake is most likely to get wrong, because nothing about it looks
    // different from the outside.
    foreach (everyNotifier(Asked::Granted) as $which => $make) {
        expect(whatBecameOfIt($make()->show(somethingWorthSaying()->whileLocked())))
            ->toBe('delivered', $which);
    }
});

// --- what only the adapter promises -------------------------------------
//
// Below the contract, because these are about composing words from the
// catalogue and the fake composes none. Asserting them above would force the
// fake to grow a translator to stay honest, which is the opposite of why it
// exists.

it('N4-R20 — the locked wording names no stack', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre)->show(somethingWorthSaying()->whileLocked());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(1)
        ->and($shown[0]['title'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('the-loft')
        ->and($shown[0]['body'])->not->toContain('backup.finished');
});

it('names the stack when the device is not locked', function (): void {
    $centre = ANotificationCentreOnAHandset::allowed();

    overAHandset($centre)->show(somethingWorthSaying());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(1)
        ->and($shown[0]['title'])->toContain('the-loft')
        ->and($shown[0]['body'])->toContain('backup.finished');
});

it('tells a device that would not show it from one that was not allowed to', function (): void {
    // The distinction a boolean could not carry, and the reason this whole
    // capability is ours. A channel the operator switched off and a platform
    // that declined outright reach a screen as two different sentences, and the
    // plugin this replaced reported both — and a missing permission — as the
    // same `false`.
    foreach ([
        [WhyNothingWasTold::NotPermitted, WhyNothingIsShown::NotificationsAreNotPermitted],
        [WhyNothingWasTold::NoSuchChannel, WhyNothingIsShown::NotificationsAreNotPermitted],
        [WhyNothingWasTold::TheTimeHasPassed, WhyNothingIsShown::TheDeviceWouldNotShowIt],
        [WhyNothingWasTold::NoSuchRepeat, WhyNothingIsShown::TheDeviceWouldNotShowIt],
        [WhyNothingWasTold::TheDeviceRefused, WhyNothingIsShown::TheDeviceWouldNotShowIt],
    ] as [$said, $means]) {
        $notifier = overAHandset(ANotificationCentreOnAHandset::allowedButRefusing($said));

        expect(whatBecameOfIt($notifier->show(somethingWorthSaying())))->toBe($means->name, $said->value);
    }
});

it('keys a repeat of the same code onto the same notification', function (): void {
    // Both platforms replace a notification whose id they have seen. Two alerts
    // about the same thing should update one entry rather than stack two, and
    // an id built from anything varying — a timestamp, a counter — would
    // quietly give the operator a pile.
    $centre = ANotificationCentreOnAHandset::allowed();
    $notifier = overAHandset($centre);

    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    $shown = $centre->shown();

    expect($shown)->toHaveCount(2)
        ->and($shown[0]['id'])->toBe($shown[1]['id']);
});
