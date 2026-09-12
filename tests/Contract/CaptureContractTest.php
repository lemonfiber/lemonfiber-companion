<?php

declare(strict_types=1);

use Lemonfiber\Native\Screen;
use Modules\Device\Api\PlatformScreen;
use Modules\Kernel\Api\Capture;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AHandsetsWindow;

// The Capture contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every test that ever asserts "this screen must not be
// photographed" will hold an `ACaptureInMemory` and never see a window, so a
// fake easier to satisfy than the platform would make `N4-R18` green against a
// window nothing protects.
//
// The rule underneath is the same one `CaptureRule.kt` and `CaptureRule.swift`
// carry, with the same six cases each. This is the third statement of it, in the
// language the application is written in, and the reason all three exist is that
// two platforms quietly disagreeing about when a window is protected is the bug
// nobody finds — each half looks right on its own.

/**
 * Every implementation of the port, each with the means to background it.
 *
 * A plain function rather than a Pest dataset, matching
 * {@see Tests\Contract\SecureStorageContractTest}: a dataset whose value is a
 * closure is resolved by Pest and handed over as one argument, so the pair comes
 * back as an array where the test wanted two parameters. Looping is longer and
 * says which implementation failed, which a dataset name does too — but this one
 * cannot be got subtly wrong.
 *
 * The second element is how each is told the app went away. They model the same
 * event and neither exposes the other's internals, so the contract asks for the
 * behaviour rather than for a shared setter.
 *
 * @return array<string, Closure(): array{Capture, Closure(): void}>
 */
function everyWindow(): array
{
    return [
        'the fake' => function (): array {
            $window = ACaptureInMemory::inFront();

            return [$window, $window->backgrounded(...)];
        },
        'the adapter' => function (): array {
            // Driven through `nativephp/mobile`'s own `FakeBridge`, which
            // intercepts `nativephp_call()` in-process — so this arm exercises
            // the real path rather than one built to resemble it.
            $handset = AHandsetsWindow::inFront();

            FakeBridge::disable();
            FakeBridge::enable()
                ->respondTo('Lemonfiber.Conceal', fn(): array => $handset->conceal())
                ->respondTo('Lemonfiber.Reveal', fn(): array => $handset->reveal())
                ->respondTo('Lemonfiber.IsProtected', fn(): array => $handset->answer());

            return [new PlatformScreen(new Screen()), $handset->backgrounded(...)];
        },
    ];
}

it('starts unprotected, in front of somebody, showing nothing guarded', function (): void {
    // The state that must *not* protect. Without this every other assertion here
    // would pass against an implementation that returned `true` always — and a
    // build refusing every screenshot forever would ship.
    foreach (everyWindow() as $which => $make) {
        [$window] = $make();

        expect($window->isProtected())->toBeFalse($which);
    }
});

it('N4-R18 — a guarded screen is protected while it is in front of you', function (): void {
    // The case a rule written as "protect when backgrounded" gets wrong. A screen
    // recording runs while the app is the thing you are looking at, so this is
    // exactly when the protection is needed.
    foreach (everyWindow() as $which => $make) {
        [$window] = $make();

        expect($window->conceal())->toBeTrue($which)
            ->and($window->isProtected())->toBeTrue($which);
    }
});

it('N4-R9 — a backgrounded app is protected, whatever it was showing', function (): void {
    foreach (everyWindow() as $which => $make) {
        [$window, $away] = $make();

        $away();

        expect($window->isProtected())->toBeTrue($which);
    }
});

it('N4-R9 — revealing while away leaves the window protected', function (): void {
    // The half a fake gets wrong by writing `reveal()` as "return false".
    // Revealing takes away N4-R18's reason and leaves N4-R9's, and an
    // implementation reporting the window unprotected here would be lying about
    // a device sitting in a task switcher.
    foreach (everyWindow() as $which => $make) {
        [$window, $away] = $make();

        $window->conceal();
        $away();

        expect($window->reveal())->toBeTrue($which)
            ->and($window->isProtected())->toBeTrue($which);
    }
});

it('stops protecting once the guarded screen is gone', function (): void {
    foreach (everyWindow() as $which => $make) {
        [$window] = $make();

        $window->conceal();

        expect($window->reveal())->toBeFalse($which)
            ->and($window->isProtected())->toBeFalse($which);
    }
});

it('is idempotent, because a screen may conceal more than once', function (): void {
    // `M3` in spirit: a screen that conceals on mount and again on a re-render
    // must not leave the window protected after a single reveal.
    foreach (everyWindow() as $which => $make) {
        [$window] = $make();

        $window->conceal();
        $window->conceal();

        expect($window->reveal())->toBeFalse($which);
    }
});
