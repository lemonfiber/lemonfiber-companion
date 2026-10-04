<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\PairingAPhone;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatMakesPairingCodes;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// Pairing a phone: nothing asked on open, a code made on a tap and followed
// while the stack makes it, the code with its line and its compare code, the
// code taken off the glass once its time is up, and a refusal in the stack's
// own words.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** When the code a case is handed stops being good. */
const THE_CODE_EXPIRES = 1_790_813_400;

/** The line the stack writes. */
const THE_PAIRING_LINE = '{"address":"https://den.local:8443","fingerprint":"abab","expires":1790813400,"stack":"00"}';

/** The machine a phone is paired with. */
function theStackAPhoneIsPairedWith(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What the stack says replacing its certificate would cost. */
const WHAT_REPLACING_IT_WOULD_COST = 'Every phone paired with this machine refuses it from then on, until it is paired again with new material.';

/** The code the stack makes, with or without a caution, and with the check code it worked out. */
function theCodeTheStackMade(string $caution = 'That address is a number.', string $compare = '22VK-KPHH-NKH9-TUWA'): APairingCode
{
    return APairingCode::made(
        APairingLine::asWritten(THE_PAIRING_LINE),
        Fingerprint::of(str_repeat('0', Fingerprint::CHARACTERS)),
        $compare,
        Instant::atEpochSeconds(THE_CODE_EXPIRES),
        'https://den.local:8443',
        $caution,
        WHAT_REPLACING_IT_WOULD_COST,
    );
}

/** The screen, with a stack it knows, a clock before the code expires, and a session where a case says. */
function thePairingScreen(
    AStackThatMakesPairingCodes $pairing,
    ?FrozenClock $clock = null,
    ?ACodeOfWhatItWasGiven $encoding = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): PairingAPhone {
    $stack = theStackAPhoneIsPairedWith();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new PairingAPhone(
        $pairing,
        $encoding ?? ACodeOfWhatItWasGiven::working(),
        $clock ?? FrozenClock::at(Instant::atEpochSeconds(THE_CODE_EXPIRES - 60)),
        AZoneThatIsSet::to('Europe/Amsterdam'),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        NoticingWhatIsNew::fromNothing(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('asks nothing on open, and offers to make a code', function (): void {
    $pairing = AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::made(theCodeTheStackMade()));
    $screen = thePairingScreen($pairing);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($pairing->asked())->toBe(0)
        ->and($pairing->followed())->toBe([])
        ->and($drawn)->toContain(__('stacks.pairing.heading'))
        ->and($drawn)->toContain(__('stacks.pairing.what_it_is'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.pairing.make'));
});

it('says the stack is working on it while it makes the code, and follows it by its handle', function (): void {
    $pairing = AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::underway(Job::named(AStackThatMakesPairingCodes::THE_JOB)));
    $screen = thePairingScreen($pairing);
    $screen->make();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.invitation.working'))
        ->and($screen->following)->toBe(AStackThatMakesPairingCodes::THE_JOB);

    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($pairing->followed())->toHaveCount(1)
        ->and($pairing->followed()[0]->shown())->toBe(AStackThatMakesPairingCodes::THE_JOB);
});

it('shows the code drawn of the line, the line to type, the compare code, until when and where', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $screen = thePairingScreen(AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::made(theCodeTheStackMade())), encoding: $encoding);
    $screen->make();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($encoding->given()?->carried())->toBe(THE_PAIRING_LINE)
        ->and($drawn)->toContain(__('stacks.pairing.or_type'))
        ->and($drawn)->toContain(THE_PAIRING_LINE)
        ->and($drawn)->toContain(__('stacks.pairing.compare'))
        ->and($drawn)->toContain('22VK-KPHH-NKH9-TUWA')
        ->and($drawn)->toContain(__('stacks.pairing.until', ['until' => '02:10:00']))
        ->and($drawn)->toContain(__('stacks.pairing.reaches', ['address' => 'https://den.local:8443']))
        ->and($drawn)->toContain('That address is a number.')
        ->and($screen->following)->toBeNull();
});

it('says what replacing the certificate costs in the stack\'s words, then that it is replaced at the machine, and offers neither', function (): void {
    $screen = thePairingScreen(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade())));
    $screen->make();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $inOrder = ['That address is a number.', WHAT_REPLACING_IT_WOULD_COST, __('stacks.pairing.replaced_at_the_machine')];

    expect(array_values(array_filter($drawn->said(), static fn(string $line): bool => in_array($line, $inOrder, strict: true))))->toBe($inOrder)
        ->and($drawn->offers())->not->toContain(WHAT_REPLACING_IT_WOULD_COST)
        ->and($drawn->offers())->not->toContain(__('stacks.pairing.replaced_at_the_machine'));

    foreach (['en', 'nl'] as $locale) {
        expect(__('stacks.pairing.replaced_at_the_machine', locale: $locale))->not->toContain('lemonfiber');
    }
});

it('shows no code whose check code this phone works out differently, and says why until another is asked for', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $pairing = AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade(compare: 'Z9JL-Q3PK-BZ6M-HRQZ')));
    $screen = thePairingScreen($pairing, encoding: $encoding);
    $screen->make();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($said)->toContain(__('stacks.pairing.checked_differently'))
        ->and($said)->not->toContain(THE_PAIRING_LINE)
        ->and($said)->not->toContain('Z9JL-Q3PK-BZ6M-HRQZ')
        ->and($said)->not->toContain(__('stacks.pairing.compare'))
        ->and($encoding->given())->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.pairing.make'));

    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pairing.checked_differently'))
        ->and($pairing->asked())->toBe(1);
});

it('draws the code once rather than on every look', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $pairing = AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade()));
    $screen = thePairingScreen($pairing, encoding: $encoding);
    $screen->make();
    WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($pairing->followed())->toBe([])
        ->and($pairing->asked())->toBe(1);
});

it('says in words where the line could not be drawn, and still gives the line to type', function (): void {
    $screen = thePairingScreen(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade(caution: ''))), encoding: ACodeOfWhatItWasGiven::drawingNothing());
    $screen->make();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.pairing.no_code'))
        ->and($drawn)->toContain(THE_PAIRING_LINE)
        ->and($drawn)->not->toContain('That address is a number.');
});

it('takes the code off the glass once its time is up, and offers a new one', function (): void {
    $clock = FrozenClock::at(Instant::atEpochSeconds(THE_CODE_EXPIRES - 60));
    $pairing = AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade()));
    $screen = thePairingScreen($pairing, clock: $clock);
    $screen->make();
    $clock->moveTo(Instant::atEpochSeconds(THE_CODE_EXPIRES));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.pairing.expired'))
        ->and($drawn)->not->toContain(THE_PAIRING_LINE)
        ->and($drawn)->not->toContain('22VK-KPHH-NKH9-TUWA')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.pairing.make_a_new_one'));

    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pairing.expired'));

    $clock->moveTo(Instant::atEpochSeconds(THE_CODE_EXPIRES - 120));
    $screen->make();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(THE_PAIRING_LINE)
        ->and($pairing->asked())->toBe(2);
});

it('says a refusal in the stack\'s own words, and offers to ask again', function (): void {
    $said = 'lemonfiber has not been served encrypted on your network, so a phone has nothing to reach';
    $screen = thePairingScreen(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::refused($said)));
    $screen->make();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain($said)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.pairing.make'));
});

it('goes back to offering a code where the stack has no outcome for the one asked for', function (): void {
    $screen = thePairingScreen(AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::ended()));
    $screen->make();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.pairing.make'))
        ->and($screen->following)->toBeNull();
});

it('says what stood in the way, and lets go of a session the stack refused', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePairingScreen(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), keychain: $keychain);
    $screen->make();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $refusing = thePairingScreen(AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);
    $refusing->make();

    expect($keychain->isHolding(theStackAPhoneIsPairedWith()->id()))->toBeFalse();
});

it('asks nothing of a stack it holds no session for, and says the session has ended', function (): void {
    $pairing = AStackThatMakesPairingCodes::answering(WhatBecameOfThePairingCode::made(theCodeTheStackMade()));
    $screen = thePairingScreen($pairing, signedIn: false);
    $screen->make();

    expect($pairing->asked())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'));
});

it('stops asking after a code whose session ended while it was being made', function (): void {
    $keychain = AKeychainInMemory::working();
    $pairing = AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::made(theCodeTheStackMade()));
    $screen = thePairingScreen($pairing, keychain: $keychain);
    $screen->make();
    $keychain->forget(theStackAPhoneIsPairedWith()->id());
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($pairing->followed())->toBe([]);
});

it('is the screen the menu opens under Access', function (): void {
    $screen = thePairingScreen(AStackThatMakesPairingCodes::whichTookItOn(WhatBecameOfThePairingCode::ended()));

    expect(NativeRouter::resolve(TheMenu::PairAPhone->screen()->forTheStack($screen->stack()->id())))->not->toBeNull()
        ->and(TheMenu::PairAPhone->group())->toBe(WhereInTheMenu::Access);
});
