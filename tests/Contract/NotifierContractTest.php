<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformNotifier;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhyNothingIsShown;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ANotificationCentre;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\APermissionAnswer;

// The Notifier contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every other test that needs "the operator was told" will hand
// its subject an `ANotifierInMemory` and never see a notification centre, so a
// fake easier to satisfy than the platform would make `N4-R4`'s refusal green
// against a centre that always says yes.
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
        'the adapter' => function () use ($standing): Notifier {
            $answer = match ($standing) {
                Asked::Granted => APermissionAnswer::granted(),
                Asked::Declined => APermissionAnswer::denied(),
                Asked::NotYet => APermissionAnswer::notDetermined(),
            };

            return new PlatformNotifier(ANotificationCentre::on($answer), $answer, Catalogue::words());
        },
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
    // Counted on the adapter, because the count is the requirement: `N4-R4` is
    // not about a return value, it is about how many times somebody was
    // interrupted.
    $answer = APermissionAnswer::denied();
    $notifier = new PlatformNotifier(ANotificationCentre::on($answer), $answer, Catalogue::words());

    $notifier->standing();
    $notifier->standing();
    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    expect($answer->prompts())->toBe(0);
});

it('N4-R4 — a declined permission is not asked for again', function (): void {
    foreach (everyNotifier(Asked::Declined) as $which => $make) {
        $notifier = $make();

        expect($notifier->ask())->toBe(Asked::Declined, $which)
            ->and($notifier->standing())->toBe(Asked::Declined, $which);
    }
});

it('N4-R4 — the prompt is raised once, and not again once answered', function (): void {
    $answer = APermissionAnswer::notDetermined();
    $notifier = new PlatformNotifier(ANotificationCentre::on($answer), $answer, Catalogue::words());

    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($answer->prompts())->toBe(1);

    // The second ask finds a standing answer and returns it without asking.
    expect($notifier->ask())->toBe(Asked::Granted)
        ->and($answer->prompts())->toBe(1);
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
    $answer = APermissionAnswer::granted();
    $centre = ANotificationCentre::on($answer);

    new PlatformNotifier($centre, $answer, Catalogue::words())->show(somethingWorthSaying()->whileLocked());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->titleSaid())->not->toContain('the-loft')
        ->and($sent[0]->bodySaid())->not->toContain('the-loft')
        ->and($sent[0]->bodySaid())->not->toContain('backup.finished');
});

it('names the stack when the device is not locked', function (): void {
    $answer = APermissionAnswer::granted();
    $centre = ANotificationCentre::on($answer);

    new PlatformNotifier($centre, $answer, Catalogue::words())->show(somethingWorthSaying());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->titleSaid())->toContain('the-loft')
        ->and($sent[0]->bodySaid())->toContain('backup.finished');
});

it('keys a repeat of the same code onto the same notification', function (): void {
    // The plugin replaces a notification whose id it has seen. Two alerts about
    // the same thing should update one entry rather than stack two, and an id
    // built from anything varying — a timestamp, a counter — would quietly give
    // the operator a pile.
    $answer = APermissionAnswer::granted();
    $centre = ANotificationCentre::on($answer);
    $notifier = new PlatformNotifier($centre, $answer, Catalogue::words());

    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(2)
        ->and($sent[0]->id)->toBe($sent[1]->id);
});
