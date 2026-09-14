<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\WhatStoppedComingIn;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatStalled;
use Tests\Support\Fakes\StacksInMemory;

// N2-R9 — stuck downloads are reachable.
//
// The screen that answers the question an operator is asked in person: *I asked
// for that film on Tuesday and it never arrived*. The requests screen can only
// half answer it, because a request marked as being fetched and one that has
// been being fetched for nine days look the same there.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose stalled downloads this screen is about. */
function theStackWhoseStallIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two stalled titles, one of which nothing is going to move. */
function aWeekOfStalledDownloads(): Stalled
{
    return Stalled::of(
        HowMuchIsShown::AllOfIt,
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
        Stuck::at('A series somebody has', 'sonarr', Stage::NotMonitored),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theStalledScreen(
    AStackThatStalled $stalling,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
    bool $signedIn = true,
): WhatStoppedComingIn {
    $stack = theStackWhoseStallIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatStoppedComingIn($stalling, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N2-R9 — shows what stopped, where it stopped, and who has it', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect($screen->howMany())->toBe(2)
        // A stack that answered is not a session that ended. `isSignedIn` is the
        // template's first branch, so a fold reporting otherwise here would put
        // `N1-R44`'s sign-in prompt in front of an operator whose session is
        // working and the rows would never be reached at all.
        ->and($screen->isSignedIn())->toBeTrue()
        // Neither of the obstacle's two keys, because nothing was met.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('');

    $rows = $screen->stalled();

    expect($rows[0]->title)->toBe('A film nobody has seen')
        ->and($rows[0]->service)->toBe('radarr')
        ->and($rows[0]->stageSaid)->toBe(Stage::Searching->saidOnTheScreen())
        ->and($rows[1]->title)->toBe('A series somebody has')
        ->and($rows[1]->service)->toBe('sonarr')
        ->and($rows[1]->stageSaid)->toBe(Stage::NotMonitored->saidOnTheScreen());
});

it('keeps the stack\'s order rather than putting the hopeless ones first', function (): void {
    // Tempting and wrong. The order is the one the work was queued in, and the
    // oldest thing stuck is usually the one that has been wrong longest — which
    // is what an operator scanning this is looking for.
    $rows = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()))->stalled();

    expect($rows[0]->stillMoving)->toBeTrue()
        ->and($rows[1]->stillMoving)->toBeFalse();
});

it('N2-R9 — says how much of what the stack holds this is', function (): void {
    // The field a screen cannot notice the absence of. A partial listing
    // rendered without it claims to be complete, and an operator shown three
    // stalled titles and told that is all of them stops looking.
    $partial = Stalled::of(
        HowMuchIsShown::SomeOfIt,
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
    );

    expect(theStalledScreen(AStackThatStalled::with($partial))->howMuchIsShown())
        ->toBe(HowMuchIsShown::SomeOfIt->saidOnTheScreen())
        ->and(theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()))->howMuchIsShown())
        ->toBe(HowMuchIsShown::AllOfIt->saidOnTheScreen());
});

it('nothing stuck is an answer, and not the same one as a stack that would not say', function (): void {
    // The distinction the whole screen turns on. A phone in flight mode must
    // not report a house where everything is arriving normally.
    $quiet = theStalledScreen(AStackThatStalled::withNothingStuck());

    expect($quiet->howMany())->toBe(0)
        ->and($quiet->met())->toBe('')
        ->and($quiet->howMuchIsShown())->toBe(HowMuchIsShown::AllOfIt->saidOnTheScreen());
});

it('N1-R10 — a stack that could not be asked says which of the six it met', function (): void {
    $screen = theStalledScreen(AStackThatStalled::met(Obstacle::DeviceHasNoNetwork));

    expect($screen->howMany())->toBe(0)
        // Meeting an obstacle is not losing the session either: the device
        // asked and was answered. Reporting otherwise would hide which of the
        // six was met behind a sign-in screen for a session that is fine.
        ->and($screen->isSignedIn())->toBeTrue()
        ->and($screen->met())->toBe(Obstacle::DeviceHasNoNetwork->said())
        ->and($screen->remedy())->toBe(Obstacle::DeviceHasNoNetwork->remedy())
        // Nothing to be complete about, so the line is not rendered at all
        // rather than claiming a listing that was never read.
        ->and($screen->howMuchIsShown())->toBe('');
});

it('N1-R44 — a device with no session for that stack is not asked to wait for one', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    $screen = theStalledScreen($stalling, signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // Nothing was met, because the app never got as far as asking — and a
        // remedy beside no obstacle would be an instruction about nothing.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('')
        // Nothing to be complete about either, so the line the listing branch
        // always renders is not rendered here at all.
        ->and($screen->howMuchIsShown())->toBe('')
        ->and($stalling->askings())->toBe(0);
});

it('N1-R17 — asks once however many accessors a frame reads', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    $screen = theStalledScreen($stalling);

    $screen->howMany();
    $screen->stalled();
    $screen->howMuchIsShown();
    $screen->met();

    expect($stalling->askings())->toBe(1);
});

it('N1-R11 — asks about the stack the route names, with the session kept for it', function (): void {
    $stalling = AStackThatStalled::with(aWeekOfStalledDownloads());
    theStalledScreen($stalling)->howMany();

    expect($stalling->askedAbout()?->id()->stored())->toBe(theStackWhoseStallIsRead()->id()->stored())
        ->and($stalling->wasGivenASession())->toBeTrue();
});

it('N1-R11 — a route naming a stack this device has forgotten is refused', function (): void {
    $screen = theStalledScreen(
        AStackThatStalled::with(aWeekOfStalledDownloads()),
        named: str_repeat('z', Nonce::SHORTEST),
    );

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment. The
    // three screens either side of this one make the same assertion.
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N2-R9 — the screen is registered under the route that reaches it', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Stuck->forTheStack(theStackWhoseStallIsRead()->id()->stored()),
    );

    expect($resolved['class'] ?? null)->toBe(WhatStoppedComingIn::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theStalledScreen(AStackThatStalled::with(aWeekOfStalledDownloads()));

    expect($screen->render()->name())->toBe('operator::what-stopped-coming-in');
});

it('N1-R3 — asking again after an obstacle asks the stack again', function (): void {
    // The action an obstacle must not take away. A stack that was asleep when
    // the screen opened may be awake now, and leaving and returning is what
    // `N1-R27` refuses by name.
    $stalling = AStackThatStalled::met(Obstacle::DeviceHasNoNetwork);
    $screen = theStalledScreen($stalling);

    $screen->howMany();
    $screen->again();
    $screen->howMany();

    expect($stalling->askings())->toBe(2);
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // The stalled screen makes the same two moves the others do, and it has to
    // make them itself: a fold cannot forget anything, and a session left in
    // the store is resumed on the next frame and refused again.
    $keychain = AKeychainInMemory::working();
    $screen = theStalledScreen(AStackThatStalled::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseStallIsRead()->id()))->toBeTrue();

    expect($screen->isSignedIn())->toBeFalse()
        // Nothing about a machine, because this is not about the machine — and
        // nothing already loaded, which `N3-R13` names separately.
        ->and($screen->met())->toBe('')
        ->and($screen->howMany())->toBe(0)
        ->and($screen->howMuchIsShown())->toBe('')
        ->and($keychain->isHolding(theStackWhoseStallIsRead()->id()))->toBeFalse();
});
