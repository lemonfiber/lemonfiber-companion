<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\VerdictsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// The chrome, asked of a rendered frame rather than of a template's text.
//
// Every other suite that touches the chrome reads the Blade — that the bars are
// top-level siblings, that each control carries a label, that no tag resolves to
// nothing. All of it is true of a template that draws none of it: a component
// whose content arrives as slot content is collected before the component's own
// template runs, which renders the padded column empty and last and passes every
// text rule there is.
//
// So this renders. What it asserts is the two things the text cannot show —
// that the chrome reaches the frame at all, and that an obstacle *replaces* the
// reading rather than sitting above it.

/** The moment a frame is read at. Named for this file (`G10`). */
const WHEN_THE_CHROME_WAS_DRAWN = 1_770_000_000;

/** The machine every frame here is about. */
function theMachineOnTheFrame(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

it('the bars reach a stack-scoped frame, and the reading is replaced rather than buried', function (): void {
    $stack = theMachineOnTheFrame();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));

    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::DeviceHasNoNetwork),
        $keychain,
        StacksInMemory::holding($stack),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    // `N1-R37` and `N1-R10`: what stood between this frame and the machine, and
    // what to do about it — both, because what happened is a fact about the
    // world and what to do about it is advice.
    expect($drawn->said())->toContain(__(Obstacle::DeviceHasNoNetwork->said()))
        ->and($drawn->said())->toContain(__(Obstacle::DeviceHasNoNetwork->remedy()))
        // The four readings of this machine, drawn by the platform's own bar.
        // Asserted by their labels because that is what somebody sees and what
        // a screen reader says; the icons and the routes are the bar's own.
        ->and($drawn->said())->toContain(__('navigation.health'))
        ->and($drawn->said())->toContain(__('navigation.services'))
        ->and($drawn->said())->toContain(__('navigation.updates'))
        ->and($drawn->said())->toContain(__('navigation.repairs'))
        // `N1-R3` keeps the way forward: an obstacle never takes the action
        // away, so the frame that says the network is down still offers the
        // retry.
        ->and($drawn->offers())->toBe([__('health.ask_again')]);
});

it('N2-R1 — the verdict a frame opens on is drawn, with its age', function (): void {
    $stack = theMachineOnTheFrame();
    $stacks = StacksInMemory::holding($stack);
    $verdicts = VerdictsInMemory::working()->lastSeen(
        $stack->id(),
        Overall::Broken,
        Instant::atEpochSeconds(WHEN_THE_CHROME_WAS_DRAWN - 7_200),
    );

    $screen = new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $verdicts,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_CHROME_WAS_DRAWN)),
        new Opening(ADeviceThatKnowsYou::willing(), $stacks, ADeviceOnANetwork::connected()),
    );

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    // The word and the machine it is about, on the first frame, without a
    // network round trip — which is the whole of what `N2-R1` asks for and what
    // `N1-R24` permits: it came out of a store, so it is retained, so it
    // carries when it was read.
    expect($drawn->said())->toContain(__(Overall::Broken->saidOnTheScreen()))
        ->and($drawn->offers())->toContain($stack->name()->shown());
});
