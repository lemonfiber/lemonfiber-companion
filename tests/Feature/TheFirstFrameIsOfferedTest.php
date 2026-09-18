<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Operator\Internal\Screens\PairByScanning;
use Modules\Operator\Internal\Screens\PairByTyping;
use Modules\Operator\Internal\Screens\YourStacks;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\VerdictsInMemory;

/** A stack this device is already paired with. */
function aPairedStack(string $called, string $seed = 'a'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

// A launch with no stack configured reaches a screen, not an empty
// surface.
//
// The route is declared by the operator surface's own service provider, which
// Laravel discovers through that module's manifest, after every provider has
// booted because the macro comes from another package. That is three mechanisms
// deep and each of them fails silently: a provider that is not discovered
// registers nothing, a callback that fires too early throws where nobody looks,
// and the application boots perfectly in both cases with no screen behind `/`.
//
// Asked of the navigation stack rather than over HTTP. `NativeRouter` is what
// the device consults, and a request in a test never enters the runloop — so an
// HTTP assertion would be testing NativePHP's test-mode stub rather than this
// application's wiring.

/**
 * The launch screen, with a store that is holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`). The
 * keychain is a parameter because two of the cases below are about what the
 * list says when a stack is signed into and when it is not, and every other
 * case does not care — so it defaults to a working store holding nothing.
 */
function theLaunchScreen(
    Stacks $stacks,
    ?AKeychainInMemory $keychain = null,
    ?AShareSheetThatWasOffered $sharing = null,
    ?VerdictsInMemory $verdicts = null,
    ?FrozenClock $clock = null,
    ?Opening $opening = null,
): YourStacks {
    return new YourStacks(
        $stacks,
        $keychain ?? AKeychainInMemory::working(),
        $sharing ?? AShareSheetThatWasOffered::working(),
        $verdicts ?? VerdictsInMemory::working(),
        $clock ?? FrozenClock::at(Instant::atEpochSeconds(1_770_000_000)),
        $opening ?? new Opening(ADeviceThatKnowsYou::willing(), $stacks, ADeviceOnANetwork::connected()),
    );
}

it('N1-R35 — the first frame is registered, and it is this screen', function (): void {
    $resolved = NativeRouter::resolve('/');

    expect($resolved)->not->toBeNull(
        'Nothing is registered for `/`. A launch with no stack configured would reach '
        . 'an empty surface, which N1-R35 refuses by name. Check that the operator '
        . "module's provider is discovered and that its booted callback ran.",
    );

    expect($resolved['class'] ?? null)->toBe(YourStacks::class);
});

it('the road out of the first frame leads somewhere that is served', function (): void {
    // The first frame is the only one a device with nothing paired can reach,
    // so this button is the whole way forward. Asking the screen rather than
    // spelling the path here is deliberate: this asserts that what the screen
    // hands the template resolves, which is the question a hand-spelled
    // expectation cannot ask — it would compare one spelling against another
    // and agree with itself while both pointed at nothing.
    //
    // One road rather than two, and the typed one is asserted where it is now
    // offered: `PairingAStackByScanningTest` asks the scanning screen for it,
    // and `F12`'s walk is what says a person can still get there from here.
    $screen = theLaunchScreen(StacksInMemory::holding());

    expect(NativeRouter::resolve($screen->scanningIsAt()))->not->toBeNull(
        'The camera road out of the first frame is not registered. An operator with '
        . 'no stack paired would tap it and stay where they are.',
    );
});

it('N1-R7 — says which stacks are already signed into, so nobody retypes a password', function (): void {
    // The reason a session is kept at all. `N1-R7` exchanges the password once,
    // and "once" is only true if the list can tell the operator which machines
    // will not ask again.
    $loft = aPairedStack('The loft', 'a');
    $shed = aPairedStack('The shed', 'b');
    $keychain = AKeychainInMemory::working();
    $keychain->keep($loft->id(), Session::of('a-session-not-a-secret'));

    $screen = theLaunchScreen(StacksInMemory::holding($loft, $shed), $keychain);

    // `N1-R11` at the one place it is visible to an operator: signed into one
    // machine and not the other, and the list says exactly that. A reader keyed
    // loosely would report both as open on the strength of one session.
    expect($screen->isSignedInto($loft))->toBeTrue()
        ->and($screen->isSignedInto($shed))->toBeFalse();
});

it('N4-R6 — a store that will not open asks for the password rather than breaking', function (): void {
    // The launch screen is the worst place to raise. A keychain that cannot be
    // read is a keychain with no session in it as far as this question goes, so
    // the operator is offered the password — which is both honest and the only
    // thing they could act on.
    $loft = aPairedStack('The loft', 'a');

    foreach ([
        'no store at all' => AKeychainInMemory::withNowhereSafe(),
        'a store that will not open' => AKeychainInMemory::thatWillNotOpen(),
    ] as $which => $keychain) {
        $screen = theLaunchScreen(StacksInMemory::holding($loft), $keychain);

        expect($screen->isSignedInto($loft))->toBeFalse($which);
    }
});

it('N1-R2 — tapping a signed-in stack goes to the report, not back to the password', function (): void {
    // What the list is *for*. An operator who is signed in wants to see their
    // machine; asking them for a password they already gave is the app having
    // forgotten what it holds.
    $loft = aPairedStack('The loft', 'a');
    $shed = aPairedStack('The shed', 'b');
    $keychain = AKeychainInMemory::working();
    $keychain->keep($loft->id(), Session::of('a-session-not-a-secret'));

    $screen = theLaunchScreen(StacksInMemory::holding($loft, $shed), $keychain);

    expect($screen->tappingGoesTo($loft))->toBe(sprintf('/stacks/%s', $loft->id()->stored()))
        ->and($screen->tappingGoesTo($shed))->toBe($screen->signInAt($shed));

    // And both URIs are ones the navigation stack knows, which a string
    // comparison cannot see: a route declared `/stacks/{stack}` and a link
    // built as `/stack/...` would both look right here and meet nowhere.
    expect(NativeRouter::resolve($screen->tappingGoesTo($loft)))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->tappingGoesTo($shed)))->not->toBeNull();
});

it('N4-R13 — assembles a report for the operator to send, and does not send it', function (): void {
    // Both clauses. The app hands the text to the platform's share sheet and
    // stops; where it goes is a choice a person makes in an app this one does
    // not know about, which is the whole difference between this and the crash
    // reporter `N4-R12` refuses.
    $sharing = AShareSheetThatWasOffered::working();
    $screen = theLaunchScreen(StacksInMemory::holding(aPairedStack('The loft', 'a')), sharing: $sharing);

    $screen->share();

    $handed = $sharing->handed();

    expect($handed)->not->toBeNull()
        ->and($handed?->named())->toBe('lemonfiber-diagnostics.txt')
        ->and($screen->sharingWent())->toBe('');
});

it('N4-R13 — the report carries no address, no session and no reading', function (): void {
    // The pressure this refuses is real: a report is useful in proportion to
    // what it contains, which is exactly what puts a token in a support bundle.
    // `Diagnostics::assemble()` refuses in its parameter list, and this is that
    // refusal checked against the text an operator would actually send.
    $loft = aPairedStack('The loft', 'a');
    $sharing = AShareSheetThatWasOffered::working();

    theLaunchScreen(StacksInMemory::holding($loft), sharing: $sharing)->share();

    $text = $sharing->handed()?->text() ?? '';

    expect($text)->toContain($loft->id()->stored())
        ->and($text)->not->toContain($loft->at()->forTheClient())
        ->and($text)->not->toContain($loft->presents()->forComparingByEye())
        ->and($text)->not->toContain('192.168');
});

it('N1-R10 — says why a report could not be handed over, and what to do', function (): void {
    // Two refusals, and only one of them is something the operator can fix.
    foreach (WhyNothingWasShared::cases() as $why) {
        $screen = theLaunchScreen(
            StacksInMemory::holding(aPairedStack('The loft', 'a')),
            sharing: AShareSheetThatWasOffered::refusing($why),
        );

        $screen->share();

        expect($screen->sharingWent())->toBe($why->saidOnTheScreen(), $why->value)
            ->and($screen->sharingRemedy())->toBe($why->remedy(), $why->value)
            ->and(__($screen->sharingWent()))->not->toBe($screen->sharingWent(), $why->value);
    }
});

it('clears the refusal once a later attempt works', function (): void {
    // A screen that kept the last failure would tell an operator their report
    // could not be sent while the sheet was open in front of them.
    $screen = theLaunchScreen(
        StacksInMemory::holding(aPairedStack('The loft', 'a')),
        sharing: AShareSheetThatWasOffered::refusing(WhyNothingWasShared::NowhereToWriteIt),
    );

    $screen->share();

    expect($screen->sharingWent())->not->toBe('');

    $working = theLaunchScreen(
        StacksInMemory::holding(aPairedStack('The loft', 'a')),
        sharing: AShareSheetThatWasOffered::working(),
    );
    $working->share();

    expect($working->sharingWent())->toBe('')
        ->and($working->sharingRemedy())->toBe('');
});

it('N1-R11 — a stack in the list leads to that stack and no other', function (): void {
    // A screen nothing navigates to is a screen nobody reaches, and the route
    // is where `N1-R11` is either kept or quietly broken: two stacks in the
    // list must lead to two URIs, and each must name the identifier this device
    // minted rather than the name an operator chose, since two machines may
    // share a name and cannot share an identifier.
    // Distinct seeds, because that is what makes them two stacks: the helper
    // derives the identifier from the seed rather than from the name, so two
    // stacks named differently and seeded the same are one machine twice.
    $loft = aPairedStack('The loft', 'a');
    $shed = aPairedStack('The shed', 'b');
    $screen = theLaunchScreen(StacksInMemory::holding($loft, $shed));

    expect($screen->signInAt($loft))->toBe(sprintf('/stacks/%s/sign-in', $loft->id()->stored()))
        ->and($screen->signInAt($shed))->not->toBe($screen->signInAt($loft));

    // And the URI it builds is one the navigation stack actually knows, which
    // is the half a string comparison cannot see: a route declared as
    // `/stacks/{stack}/sign-in` and a link built as `/stack/...` would both
    // look right here and meet nowhere.
    expect(NativeRouter::resolve($screen->signInAt($loft)))->not->toBeNull(
        'The list links to a URI the navigation stack does not know, so tapping a '
        . 'stack would reach nothing. Check the route declared in the operator '
        . "module's provider against what `signInAt()` builds.",
    );
});

it('N1-R36 — a launch with stacks configured names them rather than offering to pair', function (): void {
    // What this screen did until pairing existed: it answered the empty case
    // and only the empty case, so an operator who had just paired a stack was
    // told on the next launch that nothing was paired. A screen named for one
    // of its two states is a claim, and nothing checks a claim in a class name.
    //
    // It reads no stack to do it. N1-R36 asks for a usable frame without
    // waiting for a reading, and the cheapest way to keep that is to have
    // nothing to wait for — what is shown is retained configuration.
    $screen = theLaunchScreen(StacksInMemory::holding(aPairedStack('The loft')));

    expect($screen->nothingIsPairedYet())->toBeFalse();

    $named = [];

    foreach ($screen->configured() as $stack) {
        $named[] = $stack->name()->shown();
    }

    expect($named)->toBe(['The loft']);
});

it('N1-R11 — every configured stack is named, in the order they were paired', function (): void {
    // More than one stack is the requirement, and the order is what an operator
    // recognises their list by. `Configured` keeps it; this is what proves the
    // screen does not rearrange it on the way out.
    $screen = theLaunchScreen(StacksInMemory::holding(
        aPairedStack('The loft'),
        aPairedStack('The shed', seed: 'b'),
    ));

    $named = [];

    foreach ($screen->configured() as $stack) {
        $named[] = $stack->name()->shown();
    }

    expect($named)->toBe(['The loft', 'The shed']);
});

it('N1-R6 — both roads into pairing are registered, and each is its own screen', function (): void {
    // The requirement is that scanning and typed entry both exist, and a route
    // that is never registered is a road that does not. Each is asserted by the
    // screen behind it rather than by a count: two routes both resolving to the
    // scanning screen would satisfy a count and leave a device with no camera
    // unable to pair at all, which is the case N4-R3 is about.
    //
    // They are separate screens rather than one with a switch because ADR-0018
    // puts a software comparison on the scanned road and N1-R50 puts a person on
    // the typed one — so one of them has a confirmation step and the other must
    // not be able to reach one.
    $roads = [
        '/pair/scanned' => PairByScanning::class,
        '/pair/typed' => PairByTyping::class,
    ];

    foreach ($roads as $uri => $screen) {
        $resolved = NativeRouter::resolve($uri);

        expect($resolved)->not->toBeNull(sprintf(
            'Nothing is registered for `%s`, so that road into pairing does not exist. '
            . 'N1-R6 requires both, and N4-R3 requires the typed one specifically: it is '
            . 'what an operator with no camera, or one who declined it, has left.',
            $uri,
        ));

        expect($resolved['class'] ?? null)->toBe($screen);
    }
});

it('resolves the screen\'s view name to the surface\'s own template', function (): void {
    // `render()` answering the right name proves nothing on its own: a view
    // name is a string until something resolves it. The mutation run said so —
    // removing `loadViewsFrom` entirely, or walking up one directory too few,
    // left every other test in this file passing.
    //
    // Asked of the finder rather than of `view()->exists()`, which larastan
    // resolves at analysis time and narrows to `true` — the assertion reads as
    // dead where it is written, and the analyser says so. The finder answers
    // with the path instead of a yes, which is the stronger claim anyway: not
    // that *something* is registered under the hint, but that this file is.
    //
    // What the template then says is governed over every template at once by
    // `tests/Templates` — the vocabulary, the logic and L1's refusal of an
    // English sentence. None of that is repeated here, and none of it fires if
    // the name never reaches the file.
    expect(View::getFinder()->find('operator::your-stacks'))
        ->toEndWith('app-modules/operator/resources/views/your-stacks.blade.php');
});

it('draws the frame the surface registered, by name', function (): void {
    // A screen that answers with a view is one `tests/Templates` can read; one
    // that assembled an element tree in PHP would be invisible to every rule in
    // that suite — the vocabulary check, the screen-reader check and L1's
    // refusal of an English sentence all work over the text of a template.
    //
    // Here rather than in the operator module's own suite, which is deliberate
    // and worth knowing: `tests/Pest.php` boots the application for `Feature`,
    // `Templates` and `Contract` only. A capability is pure and needs no
    // application; a screen is the opposite of that.
    // Constructed here rather than resolved, because a test body is a closure
    // and `make()` raises a checked exception — which is the same rule that
    // put the container behind a method in the composition root.
    //
    // A fake rather than the adapter: what this asserts is the frame's name,
    // and a screen that had to reach a keychain to answer it would be a
    // different test failing for a different reason.
    expect(theLaunchScreen(StacksInMemory::working())->render()->name())
        ->toBe('operator::your-stacks');
});

it('N4-R19 — a device that will not open holds the whole screen shut', function (): void {
    // Not a banner above the list. Everything below the lock is what the lock
    // is for: the machine names, the verdicts, and the control that assembles a
    // diagnostic report about somebody's house.
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(ADeviceThatKnowsYou::refusing(), $stacks, ADeviceOnANetwork::connected()),
    );

    expect($screen->howItOpened()->isLocked)->toBeTrue();
});

it('N4-R19 — an unlocked device shows the stacks', function (): void {
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(ADeviceThatKnowsYou::willing(), $stacks, ADeviceOnANetwork::connected()),
    );

    expect($screen->howItOpened()->isLocked)->toBeFalse();
});

it('N4-R3 — a device with no screen lock is not a locked one', function (): void {
    // Refusing to open would be this app requiring something the platform does
    // not have, on a device where the operator has already decided.
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(ADeviceThatKnowsYou::withNoScreenLock(), $stacks, ADeviceOnANetwork::connected()),
    );

    expect($screen->howItOpened()->isLocked)->toBeFalse();
});

it('N4-R19 — the device is asked once for the frame, not once per field', function (): void {
    // The platform's unlock is a system dialog. A frame that asked per accessor
    // would put four of them in front of somebody, which is the behaviour that
    // teaches people the app is broken.
    $device = ADeviceThatKnowsYou::refusing();
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen($stacks, opening: new Opening($device, $stacks, ADeviceOnANetwork::connected()));

    $screen->howItOpened();
    $screen->howItOpened();
    $screen->howItOpened();

    expect($device->asked())->toBe(1);
});

it('N4-R4 — asking again is the operator saying they are ready', function (): void {
    // A button rather than an automatic retry: somebody who dismissed the
    // prompt meant it. Forgetting what was held is how the next read rebuilds
    // it, so there is one path to an answer.
    $device = ADeviceThatKnowsYou::refusing();
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen($stacks, opening: new Opening($device, $stacks, ADeviceOnANetwork::connected()));

    $screen->howItOpened();
    $screen->tryToUnlock();
    $screen->howItOpened();

    expect($device->asked())->toBe(2);
});

it('N1-R37 — a launch with no network says so, and says what to do', function (): void {
    // The half of the requirement that producing the answer does not satisfy.
    // `Obstacle::DeviceHasNoNetwork` existed from the day the obstacles were
    // written and nothing produced one; then something did, and for a while
    // nothing showed it. Either way the operator meets a stack that will not
    // answer and is sent to a cupboard to look at a machine that is fine.
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(
            ADeviceThatKnowsYou::willing(),
            $stacks,
            ADeviceOnANetwork::withNothingToReachOver(),
        ),
    );

    expect($screen->howItOpened()->met)->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($screen->howItOpened()->remedy)->toBe(Obstacle::DeviceHasNoNetwork->remedy());
});

it('N1-R37 — the stacks are still shown to a device with no network', function (): void {
    // Deliberate, and the opposite of the lock above. A retained verdict is
    // worth most when the device cannot ask for a new one, and the diagnostics
    // control at the foot of this screen is the one thing that still works when
    // nothing else does — `N4-R13` puts it here for that reason. Drawing the
    // obstacle *instead of* the list would take both away at the moment they
    // are useful.
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(
            ADeviceThatKnowsYou::willing(),
            $stacks,
            ADeviceOnANetwork::withNothingToReachOver(),
        ),
    );

    expect($screen->howItOpened()->isLocked)->toBeFalse()
        ->and($screen->nothingIsPairedYet())->toBeFalse()
        ->and($screen->configured()->isEmpty())->toBeFalse();
});

it('N1-R36 — a launch that is ready has nothing standing in the way', function (): void {
    // The banner is drawn on `!== ''`, so a fold that answered with a key for
    // every launch would put "this device has no network" above a working one.
    $stacks = StacksInMemory::holding(aPairedStack('The loft'));
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(ADeviceThatKnowsYou::willing(), $stacks, ADeviceOnANetwork::connected()),
    );

    expect($screen->howItOpened()->met)->toBe('')
        ->and($screen->howItOpened()->remedy)->toBe('');
});

it('N1-R35 — a first run has nothing standing in the way either', function (): void {
    // Nothing is wrong on a first run, and an obstacle drawn here would be the
    // app describing its own first launch as a fault.
    $stacks = StacksInMemory::working();
    $screen = theLaunchScreen(
        $stacks,
        opening: new Opening(ADeviceThatKnowsYou::willing(), $stacks, ADeviceOnANetwork::connected()),
    );

    expect($screen->howItOpened()->met)->toBe('')
        ->and($screen->howItOpened()->remedy)->toBe('');
});
