<?php

declare(strict_types=1);

use Lemonfiber\Native\Scanned;
use Lemonfiber\Native\Scanning;
use Lemonfiber\Native\WhyNothingWasRead;
use Native\Mobile\Testing\FakeBridge;

// The scanning capability's PHP face, driven through the real bridge call.
//
// `FakeBridge` is `nativephp/mobile`'s own seam: it binds into the container and
// intercepts `nativephp_call()` in-process. Using it rather than an interface of
// our own means every assertion here goes through the method name, the JSON out
// and the decoding of the answer — the whole path — instead of through something
// built to resemble it.
//
// The bridge name is written out as a literal, deliberately. `Scanning` reaches
// it through `Call`, so a test spelling it `Call::Read->value` would agree with a
// wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json` separately.
//
// What the native halves decide is not re-litigated here. `CameraRule` carries
// that, in Kotlin and in Swift, with the same seven cases each. This file is
// about what the PHP can get wrong on its own: which function it calls, what it
// sends, and what it does with an answer it cannot read.

beforeEach(function (): void {
    FakeBridge::disable();
});

/**
 * What the scanner read, or nothing where it read nothing.
 *
 * A reading of the sum type rather than a second way of asking it. `Scanned`
 * has no `wasRead()` beside `either()` on purpose — a check-then-get pair is an
 * invitation to call the getter without the check — so a suite that wants a
 * plain value builds one here, out of the arms.
 *
 * One function per question rather than one answering all three. The arms have
 * different arities, so a single helper has to flatten them into a list and a
 * list of three types is a shape the analyser cannot hold and a reader has to
 * count positions in.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of the same name are a fatal the moment both load (`G10`).
 */
function whatTheCameraRead(Scanned $scanned): string
{
    $payload = $scanned->either(
        read: static fn(string $read): ArrayObject => new ArrayObject([$read]),
        nothing: static fn(): ArrayObject => new ArrayObject(['']),
    );

    $said = $payload[0];

    return is_string($said) ? $said : '';
}

/** Why it read nothing, or nothing because it read something. */
function whyTheCameraReadNothing(Scanned $scanned): ?WhyNothingWasRead
{
    $answered = $scanned->either(
        read: static fn(): ArrayObject => new ArrayObject([null]),
        nothing: static fn(WhyNothingWasRead $why): ArrayObject => new ArrayObject([$why]),
    );

    $why = $answered[0];

    return $why instanceof WhyNothingWasRead ? $why : null;
}

/** Whether putting the question again could still change the answer. */
function whetherAskingAgainWouldHelp(Scanned $scanned): bool
{
    $answered = $scanned->either(
        read: static fn(): ArrayObject => new ArrayObject([false]),
        nothing: static fn(WhyNothingWasRead $why, bool $again): ArrayObject => new ArrayObject([$again]),
    );

    return $answered[0] === true;
}

it('asks the bridge to read, and carries the sentence the operator reads', function (): void {
    $bridge = FakeBridge::enable()
        ->respondTo('Lemonfiber.Scanning.Read', ['outcome' => 'read', 'payload' => 'lemon://pair/abc']);

    $read = new Scanning()->forAPairingCode('Point this at the code on your stack');

    $bridge->assertCalled(
        'Lemonfiber.Scanning.Read',
        static fn(array $sent): bool => $sent['prompt'] === 'Point this at the code on your stack',
    );

    expect(whatTheCameraRead($read))->toBe('lemon://pair/abc');
    expect(whyTheCameraReadNothing($read))->toBeNull();
});

it('hands back the reason and whether asking again could change it', function (): void {
    // The pair a screen chooses its sentence from. The word alone is the same
    // for a camera refused a moment ago and one refused in settings last month,
    // and those are opposite advice: try again here, or go to Settings.
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', [
        'outcome' => 'nothing',
        'because' => 'the_camera_is_not_permitted',
        'may_ask_again' => true,
    ]);

    $refused = new Scanning()->forAPairingCode('');

    expect(whyTheCameraReadNothing($refused))->toBe(WhyNothingWasRead::TheCameraIsNotPermitted);
    expect(whetherAskingAgainWouldHelp($refused))->toBeTrue();
});

it('reads a settled refusal as one nothing may ask about again', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', [
        'outcome' => 'nothing',
        'because' => 'the_camera_is_not_permitted',
        'may_ask_again' => false,
    ]);

    $settled = new Scanning()->forAPairingCode('');

    expect(whyTheCameraReadNothing($settled))->toBe(WhyNothingWasRead::TheCameraIsNotPermitted);
    expect(whetherAskingAgainWouldHelp($settled))->toBeFalse();
});

it('tells a device with no camera from one that refused', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', [
        'outcome' => 'nothing',
        'because' => 'there_is_no_camera',
        'may_ask_again' => false,
    ]);

    expect(whyTheCameraReadNothing(new Scanning()->forAPairingCode('')))
        ->toBe(WhyNothingWasRead::ThereIsNoCamera);
});

it('reads a word it does not know as somebody closing the scanner', function (): void {
    // The conservative direction, and it is the useful one rather than the
    // cautious-sounding one. Reporting an unrecognised reason as a refused
    // camera would send an operator to a Settings screen with nothing on it to
    // change, on the most common path there is.
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', [
        'outcome' => 'nothing',
        'because' => 'the_lens_was_sad',
    ]);

    expect(whyTheCameraReadNothing(new Scanning()->forAPairingCode('')))
        ->toBe(WhyNothingWasRead::TheOperatorClosedIt);
});

it('reads no answer at all as nothing having been read', function (): void {
    // Every machine that is not a handset. A stand-in that claimed a code would
    // make a test about pairing pass where there is no camera to pair with.
    FakeBridge::enable();

    expect(whyTheCameraReadNothing(new Scanning()->forAPairingCode('')))
        ->toBe(WhyNothingWasRead::TheOperatorClosedIt);
});

it('reads a read with no payload as having read nothing', function (): void {
    // The answer a half-written native half would give. An empty string is not
    // a pairing code, and handing one on would put a screen into pairing with
    // nothing to pair against.
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', ['outcome' => 'read']);

    $empty = new Scanning()->forAPairingCode('');

    expect(whatTheCameraRead($empty))->toBe('');
    expect(whyTheCameraReadNothing($empty))->toBeNull();
});

it('ignores a may-ask-again that is not a flag', function (): void {
    // A bridge that answered a string here would otherwise make every refusal
    // askable-again, which is the sentence that nags somebody who has already
    // settled the question.
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', [
        'outcome' => 'nothing',
        'because' => 'the_camera_is_not_permitted',
        'may_ask_again' => 'yes please',
    ]);

    expect(whetherAskingAgainWouldHelp(new Scanning()->forAPairingCode('')))->toBeFalse();
});
