<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatYouAreOwed;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackName;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheKeychainStillHolds;

// What a member is told before they ask for anything.
//
// The first screen of the household surface, and the one thing it does is hand
// over sentences the core wrote. What it must never do is compose one: every
// fact behind them is on the wire in parts, and a screen assembling its own
// wording for *within a limit* has decided what the household's rules mean.
//
// Here rather than in the household module's own tests because a screen
// renders, and rendering needs the application — `view()` and `__()` are not
// there in a module suite.

/** The machine a member's own reading is read from. */
function theStackAMemberReadsFrom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the core wrote to them: whether it needs approval, and what is left.
 *
 * Both halves in one answer, because they arrive in one: a member asking *can
 * I have this* is asking both at once, and a screen that could show one of
 * them would be a screen somebody has to ask twice.
 */
function whatThisMemberIsTold(): Sentences
{
    return Sentences::of(
        Sentence::of('Anything you ask for goes to whoever looks after this house first.'),
        Sentence::of('You have two left this month. It makes room again on the 1st.'),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theOwedScreen(
    AMemberWhoIsOwed $owing,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
    bool $signedIn = true,
): WhatYouAreOwed {
    $stack = theStackAMemberReadsFrom();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatYouAreOwed($owing, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N3-R4 — hands over what the core wrote, word for word and in its order', function (): void {
    $screen = theOwedScreen(AMemberWhoIsOwed::owed(whatThisMemberIsTold()));

    expect($screen->answer()->sentences)->toBe([
        'Anything you ask for goes to whoever looks after this house first.',
        'You have two left this month. It makes room again on the 1st.',
    ])
        // Neither of the obstacle's two keys, because nothing was met. The
        // template branches on these, so a word here would put an error above
        // a reading that arrived perfectly well.
        ->and($screen->answer()->met)->toBe('')
        ->and($screen->answer()->remedy)->toBe('')
        ->and($screen->answer()->cameBack())->toBeTrue();
});

it('N3-R5 — carries when a spent allowance makes room, because the core said so', function (): void {
    // The reset is a sentence rather than a sum. It is kept by the service that
    // keeps the period, so reading it needs no arithmetic that could be wrong
    // in exactly the cases somebody is waiting on.
    $said = Sentences::of(Sentence::of('Nothing left this month. It makes room again on the 1st.'));

    expect(theOwedScreen(AMemberWhoIsOwed::owed($said))->answer()->sentences)
        ->toBe(['Nothing left this month. It makes room again on the 1st.']);
});

it('N3-R3 — draws a refusal as a refusal rather than as an empty reading', function (): void {
    // The distinction the whole screen turns on. A member who may not ask for
    // something and a member with nothing to be told both arrive with no
    // sentences, and they are opposite things to read.
    $refused = theOwedScreen(AMemberWhoIsOwed::met(Obstacle::NotForThisAccount));
    $nothing = theOwedScreen(AMemberWhoIsOwed::owedNothing());

    expect($refused->answer()->cameBack())->toBeFalse()
        ->and($refused->answer()->isSignedIn)->toBeTrue()
        ->and($refused->answer()->met)->toBe('connection.not_for_this_account')
        ->and($refused->answer()->remedy)->toBe('connection.not_for_this_account_action')
        ->and($nothing->answer()->cameBack())->toBeTrue()
        ->and($nothing->answer()->sentences)->toBe([]);
});

it('N3-R2 — says nothing about entitlement that the core did not say', function (): void {
    // The app implements no permission model, so what a refusal says is the
    // obstacle's own pair of keys and nothing this module wrote. Asserted as
    // the obstacle's own rather than as literals, because a screen spelling
    // them would be a second wording able to drift from the one beside it.
    $screen = theOwedScreen(AMemberWhoIsOwed::met(Obstacle::NotForThisAccount));

    expect($screen->answer()->met)->toBe(Obstacle::NotForThisAccount->said())
        ->and($screen->answer()->remedy)->toBe(Obstacle::NotForThisAccount->remedy());
});

it('N3-R13 — a refused credential signs the device out and the session is let go of', function (): void {
    // Both halves. A fold that rendered the signed-out state while the store
    // kept the session would resume it on the next frame and be refused again,
    // and somebody would be looking at a sign-in prompt over a device that
    // still believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theOwedScreen(AMemberWhoIsOwed::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and($screen->answer()->met)->toBe('')
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAMemberReadsFrom()->id())->held)->toBeFalse();
});

it('N3-R13 — an obstacle that is not a refused credential leaves the session alone', function (): void {
    // The other direction, which matters as much: a walk out of wifi is not
    // being thrown out of the house, and a screen that forgot the session on
    // every obstacle would ask for the password every time a machine slept.
    $keychain = AKeychainInMemory::working();
    $screen = theOwedScreen(AMemberWhoIsOwed::met(Obstacle::DeviceHasNoNetwork), $keychain);

    expect($screen->answer()->isSignedIn)->toBeTrue()
        ->and(WhatTheKeychainStillHolds::forThe($keychain, theStackAMemberReadsFrom()->id())->held)->toBeTrue();
});

it('asks for nothing where this device holds no session for the machine', function (): void {
    $owing = AMemberWhoIsOwed::owed(whatThisMemberIsTold());
    $screen = theOwedScreen($owing, signedIn: false);

    expect($screen->answer()->isSignedIn)->toBeFalse()
        ->and($screen->answer()->sentences)->toBe([])
        ->and($owing->askedAbout())->toBeNull();
});

it('N1-R65 — asks once per frame, and asks again when told to', function (): void {
    // One reading per frame. A home network with a machine that may be asleep
    // is the wrong thing to talk to four times a second, and every accessor
    // here reads what one asking produced.
    $owing = AMemberWhoIsOwed::owed(whatThisMemberIsTold());
    $screen = theOwedScreen($owing);

    $screen->answer();
    $screen->answer();

    expect($owing->askings())->toBe(1);

    $screen->again();
    $screen->answer();

    expect($owing->askings())->toBe(2);
});

it('N1-R27 — refuses a route naming a machine this device has forgotten', function (): void {
    $screen = theOwedScreen(AMemberWhoIsOwed::owed(whatThisMemberIsTold()), named: 'never-paired');

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('the way back to the machine and on to signing in are both routes', function (): void {
    $screen = theOwedScreen(AMemberWhoIsOwed::owed(whatThisMemberIsTold()));
    $named = theStackAMemberReadsFrom()->id();

    expect($screen->health())->toBe(AStacksScreen::Health->forTheStack($named))
        ->and($screen->signIn())->toBe(AStacksScreen::SignIn->forTheStack($named))
        ->and(NativeRouter::resolve($screen->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->signIn()))->not->toBeNull();
});

it('N3-R4 — the screen is registered under the route that reaches it', function (): void {
    // A route nothing registered is a button that does nothing, on a handset,
    // with no error anywhere.
    $resolved = NativeRouter::resolve(AStacksScreen::Owed->forTheStack(theStackAMemberReadsFrom()->id()));

    expect($resolved['class'] ?? null)->toBe(WhatYouAreOwed::class);
});
