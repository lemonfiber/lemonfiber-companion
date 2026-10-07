<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;
use Modules\Kernel\Api\WhatToDoAboutPairing;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\PairingAPhone;
use Modules\Operator\Internal\Screens\WhatIsRunningHere;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\AStackThatChecksItself;
use Tests\Support\Fakes\AStackThatMakesPairingCodes;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Asking a stack what it offers before a button is drawn: a button for each
// answer, never hidden, each explained as itself; a screen whose reading the
// stack is too old for, opened and saying so with the road to its updates; a
// member told the house needs an update; and each stack answering for itself.
//
// Here rather than in a module's own tests because a screen renders, and
// rendering needs the application.

/** A stack whose offers decide what a screen draws. Named for this file. */
function aStackWhoseOffersAreDrawn(string $seed = 'o'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of(sprintf('Drawn %s', $seed)),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A keychain holding the operator's session for this stack. */
function aKeychainHoldingASessionFor(Stack $stack, ?Whose $whose = null): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), $whose ?? Whose::theOperator());

    return $keychain;
}

/** The pairing screen, for this stack, asking this about what it offers. */
function thePairingScreenAsking(KnowingWhatAStackOffers $offering, ?Stack $stack = null, bool $signedIn = true): PairingAPhone
{
    $stack ??= aStackWhoseOffersAreDrawn();
    $keychain = $signedIn ? aKeychainHoldingASessionFor($stack) : AKeychainInMemory::working();

    $screen = new PairingAPhone(
        AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::refused('Not now')),
        ACodeOfWhatItWasGiven::working(),
        FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)),
        AZoneThatIsSet::to('Europe/Amsterdam'),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain, offering: $offering),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The stack saying this of making a pairing code, and nothing of anything else. */
function aStackSayingOfPairing(WhetherItIsOffered $said, ?Stack $stack = null): AStackThatOffers
{
    return AStackThatOffers::onTheStack(($stack ?? aStackWhoseOffersAreDrawn())->id(), WhetherItIsOffered::NeedsANewerLemonfiber, [
        WhatToDoAboutPairing::MakeACode->asked() => $said,
    ]);
}

it('offers a button the stack offers, and says nothing beside it', function (WhetherItIsOffered $said): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePairingScreenAsking(aStackSayingOfPairing($said)));

    expect($drawn->offers())->toContain(__('stacks.pairing.make'))
        ->and($drawn->offersThatWait())->not->toContain(__('stacks.pairing.make'))
        ->and($drawn->said())->not->toContain(__('connection.not_on_this_stack'))
        ->and($drawn->said())->not->toContain(__('connection.not_set_up'));
})->with([
    'offered' => [WhetherItIsOffered::Offered],
    'a stack that could not be asked' => [WhetherItIsOffered::NotKnown],
]);

it('offers a button the stack has and has not set up, and says something has to be set up first', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePairingScreenAsking(aStackSayingOfPairing(WhetherItIsOffered::NotSetUp)));

    expect($drawn->offersThatWait())->not->toContain(__('stacks.pairing.make'))
        ->and($drawn->said())->toContain(__('connection.not_set_up'));
});

it('draws a button the account may not use, unpressable, and says it is not theirs', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePairingScreenAsking(aStackSayingOfPairing(WhetherItIsOffered::NotTheirs)));

    expect($drawn->offersThatWait())->toContain(__('stacks.pairing.make'))
        ->and($drawn->said())->toContain(__('connection.not_for_this_account'))
        ->and($drawn->offers())->not->toContain(__('connection.go_to_updates'));
});

it('draws a button the stack is too old for, unpressable, says so, and gives the road to its updates', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePairingScreenAsking(aStackSayingOfPairing(WhetherItIsOffered::NeedsANewerLemonfiber)));

    expect($drawn->offersThatWait())->toContain(__('stacks.pairing.make'))
        ->and($drawn->said())->toContain(__('connection.not_on_this_stack'))
        ->and($drawn->offers())->toContain(__('connection.go_to_updates'));
});

it('keeps a button offered where the phone holds no session to ask with', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePairingScreenAsking(aStackSayingOfPairing(WhetherItIsOffered::NeedsANewerLemonfiber), signedIn: false));

    expect($drawn->said())->not->toContain(__('connection.not_on_this_stack'));
});

it('answers each stack for itself, never with what another said', function (): void {
    $loft = aStackWhoseOffersAreDrawn('l');
    $shed = aStackWhoseOffersAreDrawn('s');
    $offering = aStackSayingOfPairing(WhetherItIsOffered::Offered, $loft)
        ->andOnTheStack($shed->id(), WhetherItIsOffered::NeedsANewerLemonfiber);

    expect(WhatTheDeviceWouldDraw::by(thePairingScreenAsking($offering, $loft))->offersThatWait())->not->toContain(__('stacks.pairing.make'))
        ->and(WhatTheDeviceWouldDraw::by(thePairingScreenAsking($offering, $shed))->offersThatWait())->toContain(__('stacks.pairing.make'));
});

/** The copy screen, whose reading met this, for a stack whose offers are asked with this. */
function theCopyScreenThatMet(Obstacle $why, ?AStackThatOffers $offering = null): WhatIsRunningHere
{
    $stack = aStackWhoseOffersAreDrawn();
    $keychain = aKeychainHoldingASessionFor($stack);
    $screen = new WhatIsRunningHere(
        AStackThatChecksItself::met($why),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain, offering: $offering ?? AStackThatOffers::everything()),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('opens a screen the stack is too old for and says so, with the road to its updates', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theCopyScreenThatMet(Obstacle::of(KindOfObstacle::NotOnThisStack)));

    expect($drawn->said())->toContain(__('connection.not_on_this_stack'))
        ->and($drawn->said())->toContain(__('connection.not_on_this_stack_action'))
        ->and($drawn->offers())->toContain(__('connection.go_to_updates'))
        ->and($drawn->offers())->toContain(__('health.ask_again'));
});

it('gives no road to the updates beside anything else that stood in the way', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theCopyScreenThatMet(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->offers())
        ->not->toContain(__('connection.go_to_updates'));
});

it('asks the stack again what it offers when the operator asks again, and not on the screen\'s own cadence', function (): void {
    $offering = AStackThatOffers::everything();
    $screen = theCopyScreenThatMet(Obstacle::of(KindOfObstacle::NotOnThisStack), $offering);

    $screen->again();

    expect($offering->wasAskedAgain(aStackWhoseOffersAreDrawn()->id()))->toBeFalse();

    $screen->askAgain();

    expect($offering->wasAskedAgain(aStackWhoseOffersAreDrawn()->id()))->toBeTrue();
});

it('tells a member the house needs an update, never which software or version', function (): void {
    $stack = aStackWhoseOffersAreDrawn();
    $keychain = aKeychainHoldingASessionFor($stack, Whose::member('ada'));
    $screen = new WhatYouCanWatch(
        AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::NotOnThisStack)),
        AMemberWhoIsOwed::owedNothing(),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $said = implode("\n", WhatTheDeviceWouldDraw::by($screen)->said());

    expect($said)->toContain(__('household.needs_an_update'))
        ->and($said)->not->toContain('lemonfiber')
        ->and($said)->not->toContain(__('connection.not_on_this_stack'));
});
