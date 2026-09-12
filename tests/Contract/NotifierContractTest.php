<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformNotifier;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhyNothingIsShown;
use Tests\Support\Fakes\ANotificationCentre;
use Tests\Support\Fakes\ANotifierInMemory;

// The Notifier contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every other test that needs "the operator was told" will hand
// its subject an `ANotifierInMemory` and never see a notification centre, so a
// fake easier to satisfy than the platform would make `N4-R13`'s refusal green
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
    return Notification::fromTheCore(StackId::rememberedAs('the-loft'), Code::of('STACK-7'));
}

/** @return array<string, Closure(): array{Notifier, bool}> */
dataset('every notifier', [
    'the fake, allowed' => fn(): array => [ANotifierInMemory::allowed(), true],
    'the fake, refused' => fn(): array => [ANotifierInMemory::refused(), false],
    'the adapter, allowed' => fn(): array => [new PlatformNotifier(ANotificationCentre::allowing()), true],
    'the adapter, refused' => fn(): array => [new PlatformNotifier(ANotificationCentre::refusing()), false],
]);

it('N4-R13 — says whether it may show anything before it is asked to', function (Notifier $notifier, bool $allowed): void {
    expect($notifier->isPermitted())->toBe($allowed);
})->with('every notifier');

it('N4-R13 — shows nothing where the operator has not allowed it', function (Notifier $notifier, bool $allowed): void {
    if ($allowed) {
        expect(whatBecameOfIt($notifier->show(somethingWorthSaying())))->toBe('delivered');

        return;
    }

    // Withheld rather than thrown, and the reason is one a screen can act on:
    // `mightBeWorthAsking()` is true here, which is what separates this from a
    // stack that no longer exists.
    expect(whatBecameOfIt($notifier->show(somethingWorthSaying())))
        ->toBe(WhyNothingIsShown::NotificationsAreNotPermitted->name);
})->with('every notifier');

it('shows the guarded form without complaint', function (Notifier $notifier, bool $allowed): void {
    // Both arms have to survive both implementations. The locked form is the
    // one a fake is most likely to get wrong, because nothing about it looks
    // different from the outside.
    $became = whatBecameOfIt($notifier->show(somethingWorthSaying()->whileLocked()));

    expect($became)->toBe($allowed ? 'delivered' : WhyNothingIsShown::NotificationsAreNotPermitted->name);
})->with('every notifier');

// --- what only the adapter promises -------------------------------------
//
// Below the contract, because these are about composing words from the
// catalogue and the fake composes none. Asserting them above would force the
// fake to grow a translator to stay honest, which is the opposite of why it
// exists.

it('N4-R20 — the locked wording names no stack', function (): void {
    $centre = ANotificationCentre::allowing();

    new PlatformNotifier($centre)->show(somethingWorthSaying()->whileLocked());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->titleSaid())->not->toContain('the-loft')
        ->and($sent[0]->bodySaid())->not->toContain('the-loft')
        ->and($sent[0]->bodySaid())->not->toContain('STACK-7');
});

it('names the stack when the device is not locked', function (): void {
    $centre = ANotificationCentre::allowing();

    new PlatformNotifier($centre)->show(somethingWorthSaying());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->titleSaid())->toContain('the-loft')
        ->and($sent[0]->bodySaid())->toContain('STACK-7');
});

it('keys a repeat of the same code onto the same notification', function (): void {
    // The plugin replaces a notification whose id it has seen. Two alerts about
    // the same thing should update one entry rather than stack two, and an id
    // built from anything varying — a timestamp, a counter — would quietly give
    // the operator a pile.
    $centre = ANotificationCentre::allowing();
    $notifier = new PlatformNotifier($centre);

    $notifier->show(somethingWorthSaying());
    $notifier->show(somethingWorthSaying());

    $sent = $centre->sent();

    expect($sent)->toHaveCount(2)
        ->and($sent[0]->id)->toBe($sent[1]->id);
});
