<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Operator\Internal\Screens\WhatTheHouseholdAsked;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AHouseholdThatAsked;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\StacksInMemory;

// N2-R11 — the requests awaiting a decision are visible from a phone.
//
// The part of a stack an operator gets asked about in person: somebody in the
// house asked for something last Tuesday and wants to know what happened. Until
// this screen existed `Wanted`, `Size` and `Waiting` were written, tested and
// reached by nothing at all.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine whose household this screen is about. */
function theStackWhoseHouseholdIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Two requests, one of which wants a decision. */
function aHouseholdMidWeek(): Requested
{
    return Requested::of(
        Wanted::of(41, 'Sam', 'A film nobody has seen', Size::guessedAt(4_000_000_000), Waiting::ForApproval),
        Wanted::of(42, 'Robin', 'A series somebody has', Size::measured(900_000_000), Waiting::Here),
    );
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theRequestsScreen(
    AHouseholdThatAsked $wanting,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
    bool $signedIn = true,
): WhatTheHouseholdAsked {
    $stack = theStackWhoseHouseholdIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    }

    $screen = new WhatTheHouseholdAsked($wanting, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N2-R11 — shows what the house asked for, and how much of it wants deciding', function (): void {
    $screen = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()));

    expect($screen->howMany())->toBe(2)
        ->and($screen->howManyWaiting())->toBe(1)
        // Neither of the obstacle's two keys, because nothing was met. The
        // template branches on these being empty, so a word here would put an
        // error above a list that arrived perfectly well.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('');
});

it('D7-R7 — every row says who asked, so a decline can reach them', function (): void {
    $rows = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()))->requests();

    expect($rows[0]->by)->toBe('Sam')
        ->and($rows[0]->title)->toBe('A film nobody has seen')
        ->and($rows[1]->by)->toBe('Robin');
});

it('D7-R4 — an estimate is labelled as one, and a measurement is not', function (): void {
    // One fact and two sentences. A screen carrying the figure and dropping the
    // word would render both of these identically, which is the failure the
    // requirement is about — and the number renders either way, so nobody
    // would notice.
    $rows = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()))->requests();

    expect($rows[0]->sizeSaid)->toBe('household.size_guessed')
        ->and($rows[1]->sizeSaid)->toBe('household.size_measured')
        ->and($rows[0]->sizeSaid)->not->toBe($rows[1]->sizeSaid);
});

it('D7-R3 — the figure is whole and under a thousand, in a unit named by a key', function (): void {
    // `L5`: a separator written into a source file is wrong in one locale by
    // construction, so nothing that leaves the fold has one. Four gigabytes is
    // `4` and `household.gigabytes`; nine hundred megabytes stays in megabytes
    // rather than becoming nought point nine of anything.
    $rows = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()))->requests();

    expect($rows[0]->sizeFigure)->toBe(4)
        ->and($rows[0]->sizeUnit)->toBe('household.gigabytes')
        ->and($rows[1]->sizeFigure)->toBe(900)
        ->and($rows[1]->sizeUnit)->toBe('household.megabytes');
});

it('D7-R3 — a figure too big for its unit moves up rather than grows', function (): void {
    // What the ladder is for, and not tidiness. Four terabytes said in
    // gigabytes is `4000` — a four-figure number, which cannot be read without
    // a separator, which `L5` says nothing leaving the fold may carry because
    // one written here is wrong in some locale by construction. Moving up a
    // unit is how the figure stays readable without one.
    //
    // Each band is entered at exactly its boundary, because the comparison is
    // `>=` and a value a byte above cannot tell that from `>`. Only at the
    // boundary do the two disagree — and disagreeing means a gigabyte shown as
    // `1000 MB`, which is the four-figure number the ladder exists to prevent,
    // arriving at the one size that reaches each band first.
    $wanted = Requested::of(
        Wanted::of(44, 'Robin', 'A megabyte exactly', Size::measured(1_000_000), Waiting::Getting),
        Wanted::of(45, 'Robin', 'A gigabyte exactly', Size::measured(1_000_000_000), Waiting::Getting),
        Wanted::of(46, 'Robin', 'A terabyte exactly', Size::measured(1_000_000_000_000), Waiting::Getting),
    );

    $rows = theRequestsScreen(AHouseholdThatAsked::wanting($wanted))->requests();

    expect([$rows[0]->sizeFigure, $rows[0]->sizeUnit])->toBe([1, 'household.megabytes'])
        ->and([$rows[1]->sizeFigure, $rows[1]->sizeUnit])->toBe([1, 'household.gigabytes'])
        ->and([$rows[2]->sizeFigure, $rows[2]->sizeUnit])->toBe([1, 'household.terabytes']);
});

it('D7-R3 — a figure between two whole ones is rounded rather than trimmed', function (): void {
    // Nearest, not toward zero and not away from it. A request of 4.6 TB shown
    // as `4 TB` understates what the operator is about to let onto their disk,
    // and one of 4.4 TB shown as `5 TB` overstates it — and the second is the
    // one that gets a request refused for being bigger than it is. Both
    // directions are asserted because a rounding that is wrong one way only
    // looks right from the other.
    // Asked of every band, because each divides by its own constant and a
    // rounding that is right in terabytes is not thereby right in megabytes.
    $wanted = Requested::of(
        Wanted::of(47, 'Robin', 'Just under, in megabytes', Size::measured(900_400_000), Waiting::Getting),
        Wanted::of(48, 'Robin', 'Just over, in megabytes', Size::measured(900_600_000), Waiting::Getting),
        Wanted::of(49, 'Robin', 'Just under, in gigabytes', Size::measured(4_400_000_000), Waiting::Getting),
        Wanted::of(50, 'Robin', 'Just over, in gigabytes', Size::measured(4_600_000_000), Waiting::Getting),
        Wanted::of(51, 'Robin', 'Just under, in terabytes', Size::measured(4_400_000_000_000), Waiting::Getting),
        Wanted::of(52, 'Robin', 'Just over, in terabytes', Size::measured(4_600_000_000_000), Waiting::Getting),
    );

    $rows = theRequestsScreen(AHouseholdThatAsked::wanting($wanted))->requests();

    expect([$rows[0]->sizeFigure, $rows[1]->sizeFigure])->toBe([900, 901])
        ->and([$rows[2]->sizeFigure, $rows[3]->sizeFigure])->toBe([4, 5])
        ->and([$rows[4]->sizeFigure, $rows[5]->sizeFigure])->toBe([4, 5]);
});

it('D7-R3 — a request nobody has sized says so rather than showing nothing', function (): void {
    $wanted = Requested::of(
        Wanted::of(43, 'Sam', 'Something nobody has sized', Size::unknown(), Waiting::ForApproval),
    );

    $row = theRequestsScreen(AHouseholdThatAsked::wanting($wanted))->requests()[0];

    // Not a zero and not a blank. The key says *we do not know*, and it names
    // no placeholder — so the figure beside it can never reach the glass.
    expect($row->sizeSaid)->toBe('household.size_unknown')
        ->and($row->sizeUnit)->toBe('')
        // The figure is carried on this arm as well, and nothing reads it —
        // which is exactly why it has to be nought rather than whatever was
        // convenient: a key naming no placeholder is the only thing keeping it
        // off the glass, and that is a property of the template, not of this.
        ->and($row->sizeFigure)->toBe(0);
});

it('a quiet week is an answer, and is not the same as a stack that did not answer', function (): void {
    // The distinction the whole carrier exists for. Folding the two together
    // would have a phone that could not reach the machine say *nobody has asked
    // for anything*, which is worse than an error: it is a confident wrong
    // answer to the question the operator opened the app with.
    $quiet = theRequestsScreen(AHouseholdThatAsked::wantingNothing());
    $unreachable = theRequestsScreen(AHouseholdThatAsked::met(Obstacle::StackDidNotAnswer));

    expect($quiet->howMany())->toBe(0)
        ->and($quiet->met())->toBe('')
        ->and($unreachable->howMany())->toBe(0)
        ->and($unreachable->met())->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($unreachable->remedy())->toBe(Obstacle::StackDidNotAnswer->remedy());
});

it('N1-R44 — a device with no session for that stack is not asked to reach it', function (): void {
    $wanting = AHouseholdThatAsked::wanting(aHouseholdMidWeek());
    $screen = theRequestsScreen($wanting, signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->howMany())->toBe(0)
        // The stack was never asked. A screen that reached out and then noticed
        // it had no session would have sent a request with nothing behind it.
        ->and($wanting->askings())->toBe(0);
});

it('N1-R17 — asks once per frame however many fields are read', function (): void {
    $wanting = AHouseholdThatAsked::wanting(aHouseholdMidWeek());
    $screen = theRequestsScreen($wanting);

    $screen->howMany();
    $screen->howManyWaiting();
    $screen->requests();
    $screen->met();

    // By identifier rather than by instance: the screen reads its stack out of
    // the list the device holds, so the object it hands the port is that one
    // and not the one this file built. `N1-R11` is about *which machine*, and
    // the identifier is what answers that.
    expect($wanting->askings())->toBe(1)
        ->and($wanting->askedAbout()?->id()->stored())
        ->toBe(theStackWhoseHouseholdIsRead()->id()->stored());
});

it('N1-R11 — a route naming a stack this device has forgotten is refused', function (): void {
    // Not an empty screen. A screen that answered for a machine it does not
    // know is the shape where one stack's requests appear under another's name.
    $screen = theRequestsScreen(
        AHouseholdThatAsked::wanting(aHouseholdMidWeek()),
        named: str_repeat('z', Nonce::SHORTEST),
    );

    expect(fn(): int => $screen->howMany())->toThrow(StackIsNotConfigured::class);
});

it('the way back to this machine and to signing in are both this screen', function (): void {
    $screen = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()));
    $named = theStackWhoseHouseholdIsRead()->id()->stored();

    expect($screen->healthIsAt())->toBe(sprintf('/stacks/%s', $named))
        ->and($screen->signInAt())->toBe(sprintf('/stacks/%s/sign-in', $named));
});

it('N2-R11 — the screen is registered under the route that reaches it', function (): void {
    // A route nothing registered is a button that does nothing. The other half
    // — that the stack screen's button points here — is asserted beside that
    // button in `SeeingHowAStackIsTest`, because `G10` has each file build its
    // own subject rather than borrow the neighbour's helper.
    $resolved = NativeRouter::resolve(sprintf(
        '/stacks/%s/requests',
        theStackWhoseHouseholdIsRead()->id()->stored(),
    ));

    expect($resolved)->not->toBeNull(
        'Nothing is registered for the requests route, so the button on the stack '
        . "screen leads nowhere. Check the operator module's provider.",
    );

    expect($resolved['class'] ?? null)->toBe(WhatTheHouseholdAsked::class);
});

it('the way back from the requests screen is a route as well', function (): void {
    // The other direction, which is the one an operator takes more often: they
    // came to see what the house asked, answered it, and want the machine.
    $screen = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()));

    expect(NativeRouter::resolve($screen->healthIsAt()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->signInAt()))->not->toBeNull();
});

it('renders the frame it is named for', function (): void {
    expect(theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()))->render()->name())
        ->toBe('operator::what-the-household-asked');
});

it('N1-R44 — a signed-out frame carries no sentence and nothing waiting behind the flag', function (): void {
    // The flag is what the template branches on, and the rest of the frame has
    // to be empty behind it rather than merely unread. A sentence or a count
    // surviving on a signed-out screen is a fact about somebody's house left
    // where whoever picked the phone up can reach it — which is the whole of
    // what this requirement is guarding, and a template is a weak place to
    // guard it.
    $screen = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()), signedIn: false);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('')
        ->and($screen->requests())->toBe([])
        ->and($screen->howManyWaiting())->toBe(0);
});

it('a stack that could not be reached is still a signed-in screen', function (): void {
    // The flag says whether this device holds a session, not whether the stack
    // replied. Collapsing the two would send somebody to sign in again over a
    // machine that is simply off — and asserted on both arms because a reader
    // that answered *signed in* for everything passes a test that only ever
    // sends one of them.
    $answered = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()));
    $unreachable = theRequestsScreen(AHouseholdThatAsked::met(Obstacle::StackDidNotAnswer));

    expect($answered->isSignedIn())->toBeTrue()
        ->and($unreachable->isSignedIn())->toBeTrue()
        ->and($unreachable->howManyWaiting())->toBe(0);
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment —
    // asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is a branch that can quietly become the other one.
    // `SeeingHowAStackIsTest` and `SigningIntoAStackTest` make the same
    // assertion about the same shape, one screen over each way.
    $screen = theRequestsScreen(AHouseholdThatAsked::wanting(aHouseholdMidWeek()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});
