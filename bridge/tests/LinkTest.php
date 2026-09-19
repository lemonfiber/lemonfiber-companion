<?php

declare(strict_types=1);

use Lemonfiber\Native\Link;
use Native\Mobile\Testing\FakeBridge;

// The link capability's PHP face, driven through the real bridge call.
//
// `FakeBridge` intercepts `nativephp_call()` in-process, so every assertion
// here goes through the function name, the JSON out and the decoding of the
// answer rather than through something built to resemble it.
//
// The bridge name is written out as a literal, deliberately. `Link` reaches it
// through `Call`, so a test spelling it `Call::LinkStatus->value` would agree
// with a wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json` separately.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('reads a reachable answer as something worth trying', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['outcome' => 'reachable']);

    expect(new Link()->isReachable())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Link.Status');
});

it('reads an unreachable answer as nothing being worth trying', function (): void {
    // The one answer that means no, and the only sentence this capability
    // exists to let a screen say: the phone has no network, rather than the
    // machine not answering.
    FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['outcome' => 'unreachable']);

    expect(new Link()->isReachable())->toBeFalse();
});

it('reads no answer at all as something worth trying', function (): void {
    // Every machine that is not a handset, and the opposite of what every other
    // capability here does with silence. Reading it as *no network* would put a
    // launch on every desktop and every test run into a state whose remedy is
    // *turn your wifi on*.
    FakeBridge::enable();

    expect(new Link()->isReachable())->toBeTrue();
});

it('reads a word it does not know as something worth trying', function (): void {
    // A shim from a newer build of this application than the PHP half. The app
    // tries and reports what it finds, which is the answer that cannot be
    // wrong in a way an operator can do nothing about.
    FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['outcome' => 'probably_maybe']);

    expect(new Link()->isReachable())->toBeTrue();
});

it('reads an answer that is not an envelope as something worth trying', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['nothing_useful' => true]);

    expect(new Link()->isReachable())->toBeTrue();
});

it('reads an outcome that is not a word as something worth trying', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Link.Status', ['outcome' => 42]);

    expect(new Link()->isReachable())->toBeTrue();
});
