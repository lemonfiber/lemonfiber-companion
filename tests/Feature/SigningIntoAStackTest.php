<?php

declare(strict_types=1);

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ADoorThatWasKnockedOn;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\StacksInMemory;

// N1-R7 and N1-R10 — the password exchanged once, and the three things that can
// come back.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite. Everything this screen decides is driven through it either way.
//
// What the screen must get right is not "did it call the port". It is that the
// operator is told which of three situations they are in, because `N1-R10`'s
// remedies differ sharply: a wrong password is retyped, a door counting
// attempts is made worse by trying, and a stack that is not answering is not a
// password question at all. A screen that flattened them would put "try again"
// under all three, and the one place that is wrong is the place it costs the
// operator their remaining attempts.

/** When the session the stack opens stops being one. */
const ENDS_AT = 1_789_380_000;

/** The stack this screen signs into, by the name the route gives it. */
function aStackToSignInto(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * The screen, with a door that answers as the test says and a store that keeps.
 *
 * The stack is put in the store *and* named in the route, because those are two
 * different facts and the screen needs both — which stack the operator picked,
 * and whether this device still holds it.
 */
function signInScreen(
    ADoorThatWasKnockedOn $door,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
): SignIntoAStack {
    $stack = aStackToSignInto();

    $screen = new SignIntoAStack(
        $door,
        $keychain ?? AKeychainInMemory::working(),
        StacksInMemory::holding($stack),
    );

    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

/** A door that opens onto a session. */
function aDoorThatOpens(): ADoorThatWasKnockedOn
{
    return ADoorThatWasKnockedOn::opening(
        Session::of('a-session-not-a-secret'),
        Instant::atEpochSeconds(ENDS_AT),
    );
}

/** The screen with a password typed into it. */
function typedPassword(SignIntoAStack $screen, string $said): SignIntoAStack
{
    $screen->__syncProperty('typed', $said);

    return $screen;
}

it('opens offering nothing, because an empty field is not an attempt', function (): void {
    // Offering it would spend a try against a door that counts them, on a
    // password nobody typed.
    $screen = signInScreen(aDoorThatOpens());

    expect($screen->went())->toBe(HowTheSignInWent::NotYet)
        ->and($screen->mayOffer())->toBeFalse();
});

it('N1-R7 — offers the password once and comes away signed in', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = typedPassword(signInScreen(aDoorThatOpens(), $keychain), 'the-operators-password');

    $screen->offer();

    expect($screen->went())->toBe(HowTheSignInWent::SignedIn)
        ->and($keychain->isHolding(aStackToSignInto()->id()))->toBeTrue();
});

it('N1-R7 — clears the field, so a second tap is a second decision', function (): void {
    // A password left in a field is a password on the glass for as long as the
    // screen is up, and after a refusal it is one the next tap would offer
    // again unchanged — an attempt the operator did not decide to make, against
    // a door that may well be counting them.
    $screen = typedPassword(signInScreen(aDoorThatOpens()), 'the-operators-password');

    $screen->offer();

    expect($screen->typed())->toBe('')
        ->and($screen->mayOffer())->toBeFalse();
});

it('N1-R11 — knocks on the stack the route names, not one of its own choosing', function (): void {
    // A screen that picked its own stack is the place two stacks come to share
    // one session.
    $door = aDoorThatOpens();

    typedPassword(signInScreen($door), 'the-operators-password')->offer();

    expect($door->knockedOn()?->id()->stored())->toBe(aStackToSignInto()->id()->stored())
        ->and($door->knocks())->toBe(1);
});

it('N1-R10 — tells the three things the operator can meet at a door apart', function (): void {
    // The whole reason this screen holds an enum rather than a boolean. Each
    // row is a different sentence and a different remedy, and only the first is
    // answered by typing the password again.
    $table = [
        [Obstacle::CredentialWasRefused, HowTheSignInWent::CredentialWasRefused, true],
        [Obstacle::TooManyAttempts, HowTheSignInWent::TooManyAttempts, false],
        [Obstacle::StackDidNotAnswer, HowTheSignInWent::StackDidNotAnswer, false],
    ];

    foreach ($table as [$met, $shown, $worthRetrying]) {
        $screen = typedPassword(
            signInScreen(ADoorThatWasKnockedOn::refusing($met)),
            'the-operators-password',
        );

        $screen->offer();

        expect($screen->went())->toBe($shown, $met->value)
            ->and($screen->went()->isWorthAnotherAttempt())->toBe($worthRetrying, $met->value)
            ->and($screen->went())->not->toBe(HowTheSignInWent::SignedIn, $met->value);
    }
});

it('answers a stack that cannot be reached as one that did not answer', function (): void {
    // Three of `Obstacle`'s six describe something that happened before any
    // credential was offered. On this screen they are all the same situation:
    // the operator did not get in and nothing about their password is known.
    foreach ([
        Obstacle::DeviceHasNoNetwork,
        Obstacle::LocalNetworkIsNotPermitted,
        Obstacle::StackIsNotTheOnePaired,
    ] as $met) {
        $screen = typedPassword(
            signInScreen(ADoorThatWasKnockedOn::refusing($met)),
            'the-operators-password',
        );

        $screen->offer();

        expect($screen->went())->toBe(HowTheSignInWent::StackDidNotAnswer, $met->value);
    }
});

it('N4-R6 — a session it could not keep is not a sign-in', function (): void {
    // The stack opened one and this phone has nowhere to put it, so the next
    // launch will ask for the password again. Saying "signed in" now and asking
    // again in a minute is being misled by an app that knew at the time.
    $table = [
        [AKeychainInMemory::withNowhereSafe(), HowTheSignInWent::NoStoreOnThisDevice],
        [AKeychainInMemory::thatWillNotOpen(), HowTheSignInWent::TheStoreWouldNotOpen],
    ];

    foreach ($table as [$keychain, $shown]) {
        $screen = typedPassword(
            signInScreen(aDoorThatOpens(), $keychain),
            'the-operators-password',
        );

        $screen->offer();

        expect($screen->went())->toBe($shown, $shown->value)
            ->and($screen->went())->not->toBe(HowTheSignInWent::SignedIn, $shown->value);
    }
});

it('does not count a field of spaces as a password', function (): void {
    // The trim is load-bearing rather than tidiness: a field holding only
    // whitespace looks typed-in and is not an attempt, and offering it would
    // spend a try against a door that counts them on a password nobody chose.
    $screen = typedPassword(signInScreen(aDoorThatOpens()), '   ');

    expect($screen->mayOffer())->toBeFalse();
});

it('offers the password field only where typing one could help', function (): void {
    // Two states say no for opposite reasons: somebody already signed in has
    // nothing to type, and somebody the door has stopped listening to would be
    // typing into a control that cannot help — and, on a stack counting
    // attempts, one that makes the wait longer. Every other state keeps the
    // field, because its remedy is something the operator does and then comes
    // straight back here.
    // Pairs rather than a keyed table: an enum cannot key a PHP array, and the
    // count below is what turns this from a list of examples into a statement
    // about every state there is.
    $offered = [
        [HowTheSignInWent::NotYet, true],
        [HowTheSignInWent::SignedIn, false],
        [HowTheSignInWent::CredentialWasRefused, true],
        [HowTheSignInWent::TooManyAttempts, false],
        [HowTheSignInWent::StackDidNotAnswer, true],
        [HowTheSignInWent::NoStoreOnThisDevice, true],
        [HowTheSignInWent::TheStoreWouldNotOpen, true],
    ];

    expect($offered)->toHaveCount(count(HowTheSignInWent::cases()));

    foreach ($offered as [$went, $keepsTheField]) {
        expect($went->mayTry())->toBe($keepsTheField, $went->value);
    }
});

it('offers the password field only where typing one could help, on the screen too', function (): void {
    // The enum answers this and the screen forwards it, and the forwarding is
    // what the template actually calls — so a screen that asked the wrong state,
    // or asked nothing, would leave the field on a door that has stopped
    // listening while the enum quietly said otherwise.
    $open = signInScreen(aDoorThatOpens());

    expect($open->mayTry())->toBeTrue();

    $counting = typedPassword(
        signInScreen(ADoorThatWasKnockedOn::refusing(Obstacle::TooManyAttempts)),
        'the-operators-password',
    );
    $counting->offer();

    expect($counting->mayTry())->toBeFalse();
});

it('N1-R2 — takes them to the report once they are in, rather than describing where it is', function (): void {
    // What they came for. The screen used to say "you can reach it from the
    // main screen" and leave them to go and do it, which is an app asking
    // somebody to navigate on its behalf.
    $screen = typedPassword(signInScreen(aDoorThatOpens()), 'the-operators-password');

    expect($screen->isSignedIn())->toBeFalse();

    $screen->offer();

    expect($screen->isSignedIn())->toBeTrue()
        ->and($screen->onwardsTo())->toBe(sprintf('/stacks/%s', aStackToSignInto()->id()->stored()))
        ->and(NativeRouter::resolve($screen->onwardsTo()))->not->toBeNull(
            'Signing in leads to a URI the navigation stack does not know, so the '
            . 'operator would tap into nothing.',
        );
});

it('offers the way onwards only to somebody who actually got in', function (): void {
    // Not the complement of `mayTry()`. A door that has stopped listening
    // offers no password field either, and offering to show a report to
    // somebody who never got in would be the screen answering a question
    // nobody asked.
    foreach ([Obstacle::CredentialWasRefused, Obstacle::TooManyAttempts, Obstacle::StackDidNotAnswer] as $met) {
        $screen = typedPassword(
            signInScreen(ADoorThatWasKnockedOn::refusing($met)),
            'the-operators-password',
        );
        $screen->offer();

        expect($screen->isSignedIn())->toBeFalse($met->value);
    }
});

it('renders the frame it is named for', function (): void {
    // Asserted because the name is the only part of a screen a unit test can
    // hold: a `view()` naming a template that does not exist raises at the
    // moment an operator opens it, on a device, which is the worst place to
    // find out. `tests/Templates` reads the file itself.
    expect(signInScreen(aDoorThatOpens())->render()->name())
        ->toBe('operator::sign-into-a-stack');
});

it('refuses a route naming a stack this device does not hold', function (): void {
    // A launch-time fault rather than a screen state: the URI names something
    // that has been forgotten, and there is no screen to draw for it.
    $screen = signInScreen(aDoorThatOpens(), named: 'a-stack-that-was-forgotten');

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('refuses a route naming no stack at all', function (): void {
    $screen = signInScreen(aDoorThatOpens(), named: '');

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment —
    // asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is a branch that can quietly become the other one.
    $screen = signInScreen(aDoorThatOpens());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});
