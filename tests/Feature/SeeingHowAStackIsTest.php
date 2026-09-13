<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;

// N1-R2 — an operator away from the machine can see whether their stack is
// doing what it should.
//
// The thing the whole application is for, and the first screen that does it.
// Pairing and signing in are both means to this end; until this existed the app
// could get in and had nothing to show.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine this screen is about. */
function theStackBeingLookedAt(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A run that found one thing worth saying. */
function aRunWithAWarning(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        ),
    ));
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theHealthScreen(
    AStackThatWasAsked $asking,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
): HowThisStackIs {
    $stack = theStackBeingLookedAt();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));

    $screen = new HowThisStackIs($asking, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N1-R2 — shows what the checks found, and what it amounts to', function (): void {
    // Both halves, because the top one is not derived from the bottom: a screen
    // working the word out from the findings would be a second opinion about a
    // judgement the engine already made.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->overall())->toBe(Overall::Degraded->saidOnTheScreen())
        ->and($screen->findings()->count())->toBe(1)
        ->and($screen->met())->toBe('');
});

it('N1-R17 — asks once however many times the frame reads it', function (): void {
    // A screen is not a poller. Every accessor reads what one asking produced,
    // and a screen that asked per accessor would open six connections to a
    // machine over somebody's home network to draw one frame.
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());
    $screen = theHealthScreen($asking);

    $screen->overall();
    $screen->findings();
    $screen->met();
    $screen->remedy();
    $screen->isSignedIn();

    expect($asking->askings())->toBe(1)
        ->and($asking->askedAbout())->toBe($screen->stack())
        ->and($asking->wasGivenASession())->toBeTrue();
});

it('N1-R10 — says what the operator met where the stack did not answer', function (): void {
    // Every obstacle, because the screen shows whichever it was and the keys
    // are derived — so a seventh case needs no edit on this screen and must not
    // arrive without a catalogue line.
    foreach (Obstacle::cases() as $why) {
        $screen = theHealthScreen(AStackThatWasAsked::met($why));

        expect($screen->met())->toBe(sprintf('connection.%s', $why->value), $why->value)
            ->and($screen->remedy())->toBe(sprintf('connection.%s_action', $why->value), $why->value)
            ->and($screen->overall())->toBe('', $why->value)
            ->and($screen->findings()->count())->toBe(0, $why->value)
            ->and(__($screen->met()))->not->toBe($screen->met(), $why->value)
            ->and(__($screen->remedy()))->not->toBe($screen->remedy(), $why->value);
    }
});

it('N1-R44 — a session that has ended sends them to sign in rather than to an error', function (): void {
    // Nothing was asked, so there is nothing to report and no obstacle to name:
    // the app did not get as far as the machine. The remedy is a screen rather
    // than a sentence, which is why signed-out is its own state.
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());
    $stack = theStackBeingLookedAt();

    $screen = new HowThisStackIs($asking, AKeychainInMemory::working(), StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->met())->toBe('')
        ->and($screen->overall())->toBe('')
        ->and($asking->askings())->toBe(0);
});

it('N4-R6 — a keychain that will not open asks for the password rather than breaking', function (): void {
    // The same call `YourStacks` makes: a store that cannot be read is a store
    // with no session in it as far as this question goes, and the honest answer
    // is the sign-in screen.
    foreach ([
        'no store at all' => AKeychainInMemory::withNowhereSafe(),
        'a store that will not open' => AKeychainInMemory::thatWillNotOpen(),
    ] as $which => $keychain) {
        $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()), $keychain);

        expect($screen->isSignedIn())->toBeFalse($which);
    }
});

it('N1-R11 — signing in again goes to this stack and no other', function (): void {
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->signInAt())
        ->toBe(sprintf('/stacks/%s/sign-in', theStackBeingLookedAt()->id()->stored()));
});

it('refuses a route naming a stack this device does not hold', function (): void {
    // A launch-time fault rather than a screen state: the URI names something
    // that has been forgotten, and there is no screen to draw for it.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()), named: 'a-stack-long-forgotten');

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('renders the frame it is named for', function (): void {
    expect(theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()))->render()->name())
        ->toBe('operator::how-this-stack-is');
});

it('says something real about every verdict a finding can carry', function (): void {
    // The list under the headline shows one line per finding, and the line is
    // the verdict's own word. A case with no catalogue line would show the key.
    foreach (Conclusion::cases() as $conclusion) {
        expect(__($conclusion->saidOnTheScreen()))
            ->not->toBe($conclusion->saidOnTheScreen(), $conclusion->value);
    }
});
