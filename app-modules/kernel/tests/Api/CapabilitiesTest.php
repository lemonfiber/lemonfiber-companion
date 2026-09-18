<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Availability;
use Modules\Kernel\Api\Capabilities;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Declared;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheStackSays;

const THE_LOFT = 'd4e5f60718293a4b';
const THE_SHED = 'e5f60718293a4b5c';

function backups(): Ability
{
    return Ability::of('backups.run');
}

function aStackCalled(string $nonce): StackId
{
    return StackId::of(Nonce::of($nonce));
}

/**
 * Which arm answered, as a word, so a test can say which one ran.
 *
 * Named for this file rather than `fold`, because a module's test files share
 * one namespace and two of the same name are a fatal the moment both load (G10).
 */
function saidAbout(WhatTheStackSays $says): string
{
    return $says->either(
        declared: static fn(Availability $availability): Code => Code::of($availability->value),
        saidNothing: static fn(): Code => Code::of('said nothing'),
    )->shown();
}

it('N1-R29 — answers what a stack can do by reading what it declared', function (): void {
    $declared = Capabilities::of(aStackCalled(THE_LOFT), Declared::of(backups(), Availability::Available));

    expect($declared->offers(backups()))->toBeTrue();
    expect(saidAbout($declared->forAbility(backups())))->toBe('available');
});

it('ARCH-R79 — a capability the stack does not have is absent, not false', function (): void {
    // The distinction the whole type is built around. `null` here is the one
    // `C2` permits by exception: the question "what does this stack say about
    // X" has no answer when the stack said nothing about X, and inventing a
    // `NotSupported` case to fill the gap would put "cannot do it" and "may not
    // do it" side by side in one list, where a screen ends up treating them the
    // same because they are the same shape.
    $declared = Capabilities::of(aStackCalled(THE_LOFT), Declared::of(backups(), Availability::Available));

    expect(saidAbout($declared->forAbility(Ability::of('tunnel.rotate'))))->toBe('said nothing');
    expect($declared->offers(Ability::of('tunnel.rotate')))->toBeFalse();
});

it('N1-R30 — present-but-unconfigured and not-permitted are each themselves', function (): void {
    // An operator told "this stack cannot back up" when the truth is "your
    // credential may not" goes looking for a feature that is right there; one
    // told "unavailable" when the truth is "not set up yet" never finds the
    // setup that would turn it on.
    $declared = Capabilities::of(
        aStackCalled(THE_LOFT),
        Declared::of(backups(), Availability::Unconfigured),
        Declared::of(Ability::of('tunnel.rotate'), Availability::NotPermitted),
    );

    expect(saidAbout($declared->forAbility(backups())))->toBe('unconfigured');
    expect(saidAbout($declared->forAbility(Ability::of('tunnel.rotate'))))->toBe('not_permitted');

    // Neither offers a button, and neither is absence.
    expect($declared->offers(backups()))->toBeFalse();
    expect($declared->offers(Ability::of('tunnel.rotate')))->toBeFalse();
});

it('N1-R31 — a capability set knows which stack declared it', function (): void {
    // The failure this refuses: a screen holding the set from the stack it was
    // showing a minute ago, now showing a different one, with buttons that are
    // right for a machine nobody is looking at.
    $loft = Capabilities::of(aStackCalled(THE_LOFT), Declared::of(backups(), Availability::Available));

    expect($loft->describes(aStackCalled(THE_LOFT)))->toBeTrue();
    expect($loft->describes(aStackCalled(THE_SHED)))->toBeFalse();
    expect($loft->stack()->is(aStackCalled(THE_LOFT)))->toBeTrue();
});

it('a stack that has declared nothing is not a stack that can do nothing', function (): void {
    // Reading the set is a call that can fail, and an empty set is what a
    // failure leaves behind. Both answer "no" to every question, and only one
    // of them is a fact about the stack — so this exists to be named at the
    // call site rather than assembled from an empty array that looks the same.
    $nothing = Capabilities::undeclared(aStackCalled(THE_LOFT));

    expect(saidAbout($nothing->forAbility(backups())))->toBe('said nothing');
    expect($nothing->offers(backups()))->toBeFalse();
    expect($nothing->describes(aStackCalled(THE_LOFT)))->toBeTrue();
});

it('ARCH-R79 — absence is read by saying what happens, not by checking for null', function (): void {
    // `C2`'s reason, in the one place it is load-bearing: a null
    // is checked at the honest call sites and skipped at the one written in a
    // hurry, and the skipped one shows a button for something the stack cannot
    // do. There is no accessor that hands back the availability on its own, so
    // there is no point at which "the stack did not say" exists as a value that
    // can be treated as "no".
    expect(get_class_methods(WhatTheStackSays::class))
        ->toBe(['declared', 'nothing', 'either', 'offersAnAction']);
});
