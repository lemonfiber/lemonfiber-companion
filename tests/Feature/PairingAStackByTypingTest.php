<?php

declare(strict_types=1);

use Modules\Connection\Api\HowThePairingWent;
use Modules\Connection\Api\Introducing;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\PairByTyping;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\Fakes\StacksInMemory;

// Pairing without a camera, and the comparison that makes it
// safe enough to allow.
//
// Typed entry is not a courtesy. It is the road on a device with no camera and
// on one whose operator declined the permission, which has to exist —
// and it is the road with no software comparison in it, because nothing scanned
// the digest. The confirmation fills that gap with the one comparison people are good at:
// the app shows what it read, in a form somebody can hold in their head, and the
// operator says whether it is the one their stack is displaying.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite. Everything the screen decides is driven through it either way.

/** The moment this screen's clock is stopped at. */
const READ_AT = 1_000;

/** Pairing material, with whatever a test needs to break about it. */
function typedCode(mixed $digest = null, mixed $expires = 2_000): string
{
    return (string) json_encode([
        'address' => 'https://192.168.1.42',
        'fingerprint' => $digest ?? str_repeat('a', Fingerprint::CHARACTERS),
        'expires' => $expires,
    ]);
}

/** The screen, with a store that either works or refuses for the reason named. */
function pairingScreen(?WhyAStackCannotBeRemembered $refusing = null): PairByTyping
{
    return new PairByTyping(
        new Introducing(SequencedEntropy::counting()),
        $refusing instanceof WhyAStackCannotBeRemembered
            ? StacksInMemory::refusing($refusing)
            : StacksInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(READ_AT)),
    );
}

/** The screen with a code and a name typed into it. */
function typedInto(PairByTyping $screen, string $code, string $name = 'The loft'): PairByTyping
{
    $screen->__syncProperty('typed', $code);
    $screen->__syncProperty('called', $name);

    return $screen;
}

it('opens waiting rather than complaining about an empty field', function (): void {
    $screen = pairingScreen();

    expect($screen->isWaiting())->toBeTrue()
        ->and($screen->isUnreadable())->toBeFalse()
        ->and($screen->mayPair())->toBeFalse();
});

it('reports back what was typed into it, which is what the template renders', function (): void {
    // The screen keeps its state `protected` and the template reads it through
    // these, so a template and a screen that disagreed about a name would show
    // the operator somebody else's. Asserted here because nothing else does:
    // the accessors have exactly one other caller and it is a Blade file, which
    // no analyser in this repository reads.
    $screen = typedInto(pairingScreen(), typedCode(), name: 'The loft');

    expect($screen->typed())->toBe(typedCode())
        ->and($screen->called())->toBe('The loft');
});

it('shows a fingerprint to compare once the code parses', function (): void {
    // The confirmation's step. The form is derived from the whole fingerprint,
    // so it is the string the stack's own screen has to be showing.
    $screen = typedInto(pairingScreen(), typedCode());

    expect($screen->isComparing())->toBeTrue()
        ->and($screen->toCompare())->not->toBe('')
        ->and($screen->mayPair())->toBeTrue();
});

it('refuses to offer pairing for a machine the operator has not named', function (): void {
    // The two things the material carries are an address and a digest,
    // and neither is a name somebody can tell two stacks apart by.
    $screen = typedInto(pairingScreen(), typedCode(), name: '  ');

    expect($screen->isComparing())->toBeTrue()
        ->and($screen->mayPair())->toBeFalse();
});

it('reads a code naming an unencrypted address as one it cannot use', function (): void {
    // The whole road, end to end, for material that promises a certificate an
    // `http://` address will never present. It reaches the operator as a code
    // that does not work, which is the only point at which they can do
    // anything about it — the alternative is a stack that pairs and is then
    // unreachable, discovered on a device after they were told otherwise.
    $screen = typedInto(pairingScreen(), (string) json_encode([
        'address' => 'http://192.168.1.42',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        'expires' => 2_000,
    ]));

    expect($screen->isUnreadable())->toBeTrue()
        ->and($screen->mayPair())->toBeFalse();
});

it('tells an expired code apart from one that was mistyped', function (): void {
    // Expiry. Checking the characters is wasted effort on a code that was typed
    // perfectly, and the only way forward is a new one from the stack.
    $expired = typedInto(pairingScreen(), typedCode(expires: READ_AT));
    $nonsense = typedInto(pairingScreen(), 'not a code');

    expect($expired->hasExpired())->toBeTrue()
        ->and($expired->isUnreadable())->toBeFalse()
        ->and($nonsense->isUnreadable())->toBeTrue()
        ->and($nonsense->hasExpired())->toBeFalse()
        ->and($expired->mayPair())->toBeFalse()
        ->and($nonsense->mayPair())->toBeFalse();
});

it('reads the code again on every frame rather than remembering one', function (): void {
    // The mistake this removes: an operator confirming a form that belonged to
    // the code they typed before the one they are looking at.
    $screen = typedInto(pairingScreen(), typedCode());
    $first = $screen->toCompare();

    $screen->__syncProperty('typed', typedCode(digest: str_repeat('b', Fingerprint::CHARACTERS)));

    expect($screen->toCompare())->not->toBe($first);
});

it('pairs the stack when the operator says the form matches', function (): void {
    $screen = typedInto(pairingScreen(), typedCode());

    $screen->confirm();

    expect($screen->went())->toBe(HowThePairingWent::Paired);
});

it('does nothing where the control is tapped before there is a form to compare', function (): void {
    // Reachable only by offering the control outside the branch that renders
    // the form, which is a template mistake rather than an operator one — and
    // What has to happen then: nothing.
    $screen = typedInto(pairingScreen(), 'not a code');

    $screen->confirm();

    expect($screen->went())->toBe(HowThePairingWent::NotYet);
});

it('says the pairing did not happen where the stack could not be written down', function (): void {
    // An operator told "paired" who finds nothing on the next launch was misled
    // by an app that had the information at the time.
    $noStore = typedInto(pairingScreen(WhyAStackCannotBeRemembered::DeviceHasNoSecureStorage), typedCode());
    $shut = typedInto(pairingScreen(WhyAStackCannotBeRemembered::StoreWouldNotOpen), typedCode());

    $noStore->confirm();
    $shut->confirm();

    expect($noStore->went())->toBe(HowThePairingWent::NoStoreOnThisDevice)
        ->and($shut->went())->toBe(HowThePairingWent::TheStoreWouldNotOpen);
});

it('says something under the code field in every state, and it is a real sentence', function (): void {
    // Two halves. The screen hands over a key rather than the words, because A4
    // keeps the translator out of a class that did not ask for one — so the key
    // it hands over has to be one the catalogue holds, and a typo in it renders
    // as the key itself on a device.
    $screens = [
        pairingScreen(),
        typedInto(pairingScreen(), 'not a code'),
        typedInto(pairingScreen(), typedCode(expires: READ_AT)),
        typedInto(pairingScreen(), typedCode()),
    ];

    $said = [];

    foreach ($screens as $screen) {
        $key = $screen->supportingTheCode();
        $said[] = $key;

        expect(__($key))->not->toBe($key, sprintf('%s is not in the catalogue', $key));
    }

    expect(count(array_unique($said)))->toBe(count($screens));
});

it('renders the frame its template names', function (): void {
    expect(pairingScreen()->render()->name())->toBe('operator::pair-by-typing');
});

it('says what this screen is for until there is an outcome, then what happened', function (): void {
    // The headline and the line under it are one pair rather than four
    // branches. Before the operator confirms anything, what a screen says is
    // what that screen is *for* — and the two pairing roads are for different
    // things, so the screen answers rather than the outcome. After, the outcome
    // answers, derived from its own case, so a fourth one needs no edit here.
    $waiting = typedInto(pairingScreen(), typedCode());

    expect($waiting->headline())->toBe(HowItWasRead::Typed->askedFor())
        ->and($waiting->supporting())->toBe(HowItWasRead::Typed->howToStart());

    $waiting->confirm();

    expect($waiting->headline())->toBe('connection.paired')
        ->and($waiting->supporting())->toBe('connection.paired_action');

    // And a refusal says its own pair rather than the paired one, which is the
    // whole of why a pairing that was not written down is not a pairing.
    $shut = typedInto(pairingScreen(WhyAStackCannotBeRemembered::StoreWouldNotOpen), typedCode());
    $shut->confirm();

    expect($shut->headline())->toBe('connection.store_would_not_open')
        ->and($shut->supporting())->toBe('connection.store_would_not_open_action');
});

it('N1-R2 — a paired stack leads to signing into it, rather than to a sentence about where it is', function (): void {
    // Pairing is not signing in: the machine has been introduced and this
    // device holds no session for it. So the way onwards is the password — and
    // it is a tap, rather than "you can reach it from the main screen" and an
    // operator left to go and do it.
    $screen = typedInto(pairingScreen(), typedCode());

    $screen->confirm();

    expect($screen->went())->toBe(HowThePairingWent::Paired)
        ->and($screen->onwardsTo())->toStartWith('/stacks/')
        ->and($screen->onwardsTo())->toEndWith('/sign-in')
        ->and(NativeRouter::resolve($screen->onwardsTo()))->not->toBeNull(
            'Pairing leads to a URI the navigation stack does not know.',
        );
});

it('leads nowhere until something has actually been paired', function (): void {
    // The identifier is written at the moment the stack is made rather than
    // read back from the store, because "which one did I just add" is a
    // question a list of stacks does not answer — and until it is written there
    // is no stack to lead to. The template asks `isPaired()` before it asks
    // this, so the empty case is never rendered; it is asserted because a
    // screen that answered `/stacks//sign-in` would look like a route and
    // resolve to nothing.
    $screen = typedInto(pairingScreen(), typedCode());

    expect($screen->onwardsTo())->toBe('/stacks//sign-in')
        ->and(NativeRouter::resolve($screen->onwardsTo()))->toBeNull();

    $screen->confirm();

    expect($screen->onwardsTo())->not->toBe('/stacks//sign-in');
});

it('offers a way out of pairing at any point', function (): void {
    // The sibling of the case on the scanning road, and the same reason: the
    // typed road is where somebody lands when the camera is refused, and being
    // able to abandon it is what keeps that from being a trap.
    expect(pairingScreen()->theListIsAt())->toBe(AScreenWithoutAStack::TheList->value);
});
