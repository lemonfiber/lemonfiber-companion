<?php

declare(strict_types=1);

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\Introducing;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\PairByScanning;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\Fakes\StacksInMemory;

// N1-R6 and ADR-0018 — pairing with a camera, where nobody compares hex.
//
// The road the design was built around. The digest arrives in the payload, so
// the comparison happens in software and there is no confirmation step — which
// is the one way this screen genuinely differs from the typed one, and the
// difference the tests below are mostly about.
//
// Here rather than in the operator module's own suite because a screen renders,
// and rendering needs the application: `view()` and `__()` are not there in a
// module suite.

/** The moment every code in this file is scanned at. */
const SCANNED_AT = 1_000;

/** Pairing material, with whatever a test needs to break about it. */
function scannedCode(mixed $expires = 2_000): string
{
    return (string) json_encode([
        'address' => 'https://192.168.1.42',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        'expires' => $expires,
    ]);
}

/** The screen, with a camera that sees what a test says and a store that works. */
function scanningScreen(ACameraInMemory $camera, ?WhyAStackCannotBeRemembered $refusing = null): PairByScanning
{
    return new PairByScanning(
        $camera,
        new Introducing(SequencedEntropy::counting()),
        $refusing instanceof WhyAStackCannotBeRemembered
            ? StacksInMemory::refusing($refusing)
            : StacksInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(SCANNED_AT)),
    );
}

/** The screen with a name typed into it. */
function named(PairByScanning $screen, string $name = 'The loft'): PairByScanning
{
    $screen->__syncProperty('called', $name);

    return $screen;
}

it('leaves the camera shut for a machine the operator has not named', function (): void {
    // N1-R11 needs a name and the material carries none — an address and a
    // digest are not two names. Asking first also means the scan either
    // completes the pairing or does not, with no paired stack left waiting on a
    // text field.
    $camera = ACameraInMemory::reading(scannedCode());
    $screen = scanningScreen($camera);

    $screen->scan();

    expect($screen->mayScan())->toBeFalse()
        ->and($camera->opened())->toBe(0)
        ->and($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('leaves the camera shut for a name that is only spaces', function (): void {
    // A phone's keyboard puts a space in on its own, and a stack called " " is
    // a stack the operator cannot tell from any other. `StackName::of()` would
    // refuse it — which on this road means refusing it *after* the camera has
    // read a code, so the guard is here where it can be refused before.
    $camera = ACameraInMemory::reading(scannedCode());
    $screen = named(scanningScreen($camera), '   ');

    $screen->scan();

    expect($screen->mayScan())->toBeFalse()
        ->and($camera->opened())->toBe(0)
        ->and($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('reports back the name it was given, which is what the template renders', function (): void {
    // The screen keeps its state `protected` and the template reads it through
    // this, so a template and a screen that disagreed would show the operator
    // somebody else's name on the paired screen. Asserted here because nothing
    // else does: the accessor has exactly one other caller and it is a Blade
    // file, which no analyser in this repository reads.
    expect(named(scanningScreen(ACameraInMemory::reading(scannedCode())), 'The shed')->called())
        ->toBe('The shed');
});

it('says nothing about the camera before anybody has opened it', function (): void {
    // The state the screen opens in, and the one the template guards on. An
    // empty answer here is what stops a screen showing a sentence about a
    // refusal nobody met — and a key to look up where there is none would be
    // the key itself, rendered.
    $screen = named(scanningScreen(ACameraInMemory::reading(scannedCode())));

    expect($screen->nothingWasScanned())->toBeFalse()
        ->and($screen->whyNothingCameBack())->toBe('')
        ->and($screen->remedyForTheCamera())->toBe('');
});

it('pairs the stack from what the camera read, with nothing to confirm', function (): void {
    // ADR-0018's whole point. The digest came in the payload, so there is no
    // fingerprint on the glass and no operator answer — `Introducing::stack()`
    // is the scanned road and this screen cannot reach the other one.
    $screen = named(scanningScreen(ACameraInMemory::reading(scannedCode())));

    $screen->scan();

    expect($screen->went())->toBe(HowThePairingWent::Paired);
});

it('N4-R3 — a refused camera is told apart from one somebody closed', function (): void {
    // Three reasons, three things to do about them, and this is the screen that
    // has to say which. "Open Settings" is advice that wastes an operator's
    // time on two of the three.
    //
    // Asserted as three distinct remedies rather than as a boolean, which is
    // the stronger claim and the one that was wrong: a `settingsWouldHelp()`
    // flag chose between "open Settings" and one generic alternative, so a
    // closed scanner and a phone with no camera were answered with the same
    // sentence — written for neither. Each reason now carries its own.
    $said = [];

    foreach (WhyNothingWasScanned::cases() as $why) {
        $screen = named(scanningScreen(ACameraInMemory::answering($why)));
        $screen->scan();

        expect($screen->nothingWasScanned())->toBeTrue($why->value)
            ->and($screen->went())->toBe(HowThePairingWent::NotYet, $why->value)
            ->and(__($screen->remedyForTheCamera()))
            ->not->toBe($screen->remedyForTheCamera(), $why->value);

        $said[] = $screen->remedyForTheCamera();
    }

    expect($said)->toHaveCount(count(array_unique($said)));
});

it('says something about every way the camera can come back empty, and it is a real sentence', function (): void {
    // The screen hands over a key rather than the words, because A4 keeps the
    // translator out of a class that did not ask for one — so the key has to be
    // one the catalogue holds, and a typo renders as the key itself on a device.
    $said = [];

    foreach (WhyNothingWasScanned::cases() as $why) {
        $screen = named(scanningScreen(ACameraInMemory::answering($why)));
        $screen->scan();

        $key = $screen->whyNothingCameBack();
        $said[] = $key;

        expect(__($key))->not->toBe($key, sprintf('%s is not in the catalogue', $key));
    }

    expect(count(array_unique($said)))->toBe(count(WhyNothingWasScanned::cases()));
});

it('tells a code it could not read apart from a camera that came back empty', function (): void {
    // A product barcode in frame, or a code from something that is not a stack.
    // The camera did its job; what it read is not pairing material, and those
    // are two different sentences.
    $screen = named(scanningScreen(ACameraInMemory::reading('not a pairing code')));

    $screen->scan();

    expect($screen->codeWasUnreadable())->toBeTrue()
        ->and($screen->nothingWasScanned())->toBeFalse()
        ->and($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('refuses a scanned code naming an address that presents no certificate', function (): void {
    // Both roads parse through the same named constructor, so the refusal is
    // true of both. Worth asserting on this one anyway: a camera reading a
    // stack's own screen is the road somebody assumes is safe by construction,
    // which is exactly why it needs the assertion rather than the assumption.
    $screen = named(scanningScreen(ACameraInMemory::reading((string) json_encode([
        'address' => 'http://192.168.1.42',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        'expires' => 2_000,
    ]))));

    $screen->scan();

    expect($screen->codeWasUnreadable())->toBeTrue()
        ->and($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('N1-R49 — an expired code is refused on this road too', function (): void {
    // Both roads parse through the same named constructor, which is what keeps
    // the expiry true of both. A second parser here would be the one that
    // stopped being tested.
    $screen = named(scanningScreen(ACameraInMemory::reading(scannedCode(expires: SCANNED_AT))));

    $screen->scan();

    expect($screen->codeWasUnreadable())->toBeTrue()
        ->and($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('clears what the last attempt said before opening the camera again', function (): void {
    // An operator who was told the camera was refused, granted it in Settings,
    // came back and scanned successfully should not still be reading the
    // refusal. State from an attempt that is over is state about something that
    // is no longer true.
    $screen = named(scanningScreen(ACameraInMemory::answering(WhyNothingWasScanned::TheOperatorClosedIt)));
    $screen->scan();

    expect($screen->nothingWasScanned())->toBeTrue();

    $working = named(scanningScreen(ACameraInMemory::reading(scannedCode())));
    $working->scan();

    expect($working->nothingWasScanned())->toBeFalse()
        ->and($working->codeWasUnreadable())->toBeFalse();
});

it('says the pairing did not happen where the stack could not be written down', function (): void {
    // An operator told "paired" who finds nothing on the next launch was misled
    // by an app that had the information at the time.
    $noStore = named(scanningScreen(
        ACameraInMemory::reading(scannedCode()),
        WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage,
    ));
    $shut = named(scanningScreen(
        ACameraInMemory::reading(scannedCode()),
        WhyAStackCannotBeRemembered::StoreWouldNotOpen,
    ));

    $noStore->scan();
    $shut->scan();

    expect($noStore->went())->toBe(HowThePairingWent::NoStoreOnThisDevice)
        ->and($shut->went())->toBe(HowThePairingWent::TheStoreWouldNotOpen);
});

it('renders the frame its template names', function (): void {
    expect(scanningScreen(ACameraInMemory::reading(scannedCode()))->render()->name())
        ->toBe('operator::pair-by-scanning');
});

it('says what this screen is for until there is an outcome, then what happened', function (): void {
    // The same pair as the typed road, and deliberately not the same opening
    // sentence: this screen points a camera and its sibling takes dictation, so
    // an outcome cannot answer for both. Everything after the confirmation is
    // shared, derived from the outcome's own case.
    $screen = named(scanningScreen(ACameraInMemory::reading(scannedCode())));

    expect($screen->headline())->toBe(HowItWasRead::Scanned->askedFor())
        ->and($screen->supporting())->toBe(HowItWasRead::Scanned->howToStart())
        ->and($screen->headline())->not->toBe(HowItWasRead::Typed->askedFor());

    $screen->scan();

    expect($screen->headline())->toBe('connection.paired')
        ->and($screen->supporting())->toBe('connection.paired_action');
});

it('N1-R2 — a paired stack leads to signing into it, rather than to a sentence about where it is', function (): void {
    // The same onward step as the typed road, and for the same reason: pairing
    // introduces a machine and leaves this device holding no session for it.
    $screen = named(scanningScreen(ACameraInMemory::reading(scannedCode())));

    $screen->scan();

    expect($screen->went())->toBe(HowThePairingWent::Paired)
        ->and($screen->onwardsTo())->toStartWith('/stacks/')
        ->and($screen->onwardsTo())->toEndWith('/sign-in')
        ->and(NativeRouter::resolve($screen->onwardsTo()))->not->toBeNull(
            'Pairing leads to a URI the navigation stack does not know.',
        );
});

it('offers the typed road wherever it tells somebody to type the code instead', function (): void {
    // The sentence and the way to act on it have to arrive together. This
    // screen already said *you can type the pairing code instead* on every one
    // of these, and offered no route — an instruction the app does not honour,
    // on a screen that is one of two things on an empty list the first time
    // somebody opens the app.
    foreach (WhyNothingWasScanned::cases() as $why) {
        $screen = named(scanningScreen(ACameraInMemory::answering($why)));
        $screen->scan();

        expect($screen->theTypedRoadWouldHelp())->toBeTrue($why->value);
    }
});

it('offers the typed road for a code the camera read and could not use', function (): void {
    // The other arm, and the one somebody meets holding a working camera: a
    // code that will not scan is exactly the moment to type it.
    $screen = named(scanningScreen(ACameraInMemory::reading('not a pairing code at all')));
    $screen->scan();

    expect($screen->codeWasUnreadable())->toBeTrue()
        ->and($screen->theTypedRoadWouldHelp())->toBeTrue();
});

it('does not offer the typed road before anybody has tried the camera', function (): void {
    // Offered as a remedy rather than as a second front door. `YourStacks`
    // already puts both roads side by side, which is where somebody chooses;
    // here it answers something that has just gone wrong.
    expect(named(scanningScreen(ACameraInMemory::reading(scannedCode())))->theTypedRoadWouldHelp())
        ->toBeFalse();
});

it('sends the typed road to the screen the provider registers for it', function (): void {
    // Read off the case rather than spelled here, so a rename cannot leave this
    // button pointing at nothing.
    expect(named(scanningScreen(ACameraInMemory::reading(scannedCode())))->typingIsAt())
        ->toBe(AScreenWithoutAStack::PairByTyping->value);
});
