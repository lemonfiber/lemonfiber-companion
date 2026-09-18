<?php

declare(strict_types=1);

use Modules\Dx\Api\ClientsThatReachNothing;
use Modules\Dx\Internal\TheStandIns;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Sdk\Api\PinnedClients;
use Modules\Vault\Api\PlatformStacks;

// The register that goes red if the suite is ever pointed at a stand-in.
//
// `modules/dx` is a development dependency, which means it is installed while
// this suite runs and Laravel discovers its provider along with every other. So
// the thing standing between eleven hundred tests and a stand-in is one config
// value, and a config value with nothing watching it is a config value that
// changes.
//
// What makes that worth a test of its own rather than a comment: the failure is
// silent and total. A suite pointed at `ClientsThatReachNothing` does not go
// red — it goes green, against payloads no stack ever sent, and every assertion
// about what a screen does with a real answer becomes an assertion about what
// it does with a generated one. Nothing else in the repository could see it,
// because everything else is downstream of the binding.
//
// Both directions are asserted. That the default is off is half of it; that the
// switch works at all is the other half, and a switch nothing exercises is a
// switch that quietly stops working — which would be found on a device, by
// somebody wondering why the stand-in does nothing.

/**
 * What the container hands back for one port.
 *
 * A named function rather than a `make()` at the call site, because the
 * analyser refuses a checked exception raised inside a closure and every Pest
 * body is one. Left to raise rather than caught: a port that cannot be built is
 * this rule failing, and it fails loudest with the container's own message,
 * which names what was missing.
 */
function whatTheContainerHandsBackFor(string $port): object
{
    $built = app()->make($port);

    return is_object($built)
        ? $built
        : throw new RuntimeException(sprintf('The container answered %s with something that is not an object.', $port));
}

/** The configuration repository, asked for the way every other binding is. */
function theConfigurationTheApplicationHolds(): object
{
    return whatTheContainerHandsBackFor('config');
}

/**
 * The `Stacks` the container currently answers with, narrowed.
 *
 * Narrowed with a check rather than declared, because the container answers
 * `mixed` and these rules need the port's own methods — asking a device whether
 * it holds a pairing is the whole assertion. It fails loudly if a binding ever
 * answers with something else, which is the other thing worth knowing.
 */
function whateverIsBoundToStacks(): Stacks
{
    $bound = whatTheContainerHandsBackFor(Stacks::class);

    return $bound instanceof Stacks
        ? $bound
        : throw new RuntimeException('Stacks is bound to something that is not a Stacks.');
}

/** The `SecureStorage` the container currently answers with, narrowed the same way. */
function whateverIsBoundToSessions(): SecureStorage
{
    $bound = whatTheContainerHandsBackFor(SecureStorage::class);

    return $bound instanceof SecureStorage
        ? $bound
        : throw new RuntimeException('SecureStorage is bound to something that is not one.');
}

/**
 * The first stack this device holds, for a rule that needs one.
 *
 * Taken from the port rather than built here, so it is the stack the stand-in
 * actually seeded — a stack assembled beside this would be a second opinion
 * about what the device holds, and the two would drift.
 */
function theFirstStackThisDeviceHolds(): Stack
{
    foreach (whateverIsBoundToStacks()->configured() as $stack) {
        return $stack;
    }

    throw new RuntimeException('This device holds no stack, so there is nothing to keep a session for.');
}

it('resolves the real adapter, because nothing asked for a stand-in', function (): void {
    expect(whatTheContainerHandsBackFor(Reaching::class))->toBeInstanceOf(PinnedClients::class);
});

it('has a provider that binds nothing while the switch is off', function (): void {
    // Asked of the provider rather than of the container, so this still means
    // something on the day another provider happens to bind the same port. The
    // question is whether *this* one stayed out of the way.
    $before = whatTheContainerHandsBackFor(Reaching::class);

    app()->register(new DxServiceProvider(app()), force: true);

    expect(whatTheContainerHandsBackFor(Reaching::class))->toBeInstanceOf($before::class);
});

it('binds nothing when the application has no configuration at all', function (): void {
    // The provider asks the container for the config repository, and the
    // container declares that it may fail to build one. There is no state of a
    // booted application where it does — so this test manufactures one, because
    // the alternative is a `catch` nothing has ever executed, and a defensive
    // arm nobody reaches is indistinguishable from a broken one.
    //
    // What it pins is the answer, not the mechanism: *nobody said* has to read
    // as *nobody asked*. A provider that took a missing config as permission
    // would be a module that takes over the application's ports whenever
    // something else went wrong first.
    $settings = theConfigurationTheApplicationHolds();

    app()->forgetInstance('config');
    app()->register(new DxServiceProvider(app()), force: true);

    // Put back before anything asserts, so a failure here reads as this rule
    // failing rather than as the rest of the run coming apart.
    app()->instance('config', $settings);

    expect(whatTheContainerHandsBackFor(Reaching::class))->toBeInstanceOf(PinnedClients::class);
});

it('Q-R72 — binds every stand-in when one is asked for', function (): void {
    config(['dx.stands_in' => true]);

    app()->register(new DxServiceProvider(app()), force: true);

    expect(whatTheContainerHandsBackFor(Reaching::class))->toBeInstanceOf(ClientsThatReachNothing::class);
});

it('N1-R57 — a device with stand-ins on is already introduced to a machine', function (): void {
    // The rule below asks whether each port answers with the class its stand-in
    // names, and for this one that question is too weak to mean anything: the
    // stand-in *is* `PlatformStacks`, the same class the real binding gives,
    // over a store that lives in this process instead of the keychain. Both
    // answers are the same class and only one of them holds a pairing.
    //
    // So this asks the difference that matters, which is also the whole of what
    // *Operate* wants: every screen of the app sits behind a pairing, and a
    // device holding none reaches exactly one frame.
    expect(whatTheContainerHandsBackFor(Stacks::class))->toBeInstanceOf(PlatformStacks::class);

    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    // Asked of a freshly built adapter rather than the one that seeded it, so
    // this proves the store behind them is shared — which is what makes a
    // pairing made on one screen still there on the next.
    $stacks = whateverIsBoundToStacks();

    expect($stacks->holdsAny())->toBeTrue()
        ->and($stacks->configured()->isEmpty())->toBeFalse();
});

it('N1-R60 — a session kept with stand-ins on reaches no device', function (): void {
    // The gap this closes is not hypothetical and not the stand-in's own doing:
    // `SignIntoAStack` keeps what a sign-in came back with, and with stand-ins
    // on what came back was assembled from the contract. Without a stand-in at
    // this port a fabricated session would be written into the operator's
    // actual keychain and outlive the run that made it — which is refused
    // in as many words.
    //
    // Proved by a round-trip rather than by naming the adapter, because the
    // adapter is the shipped one either way: `PlatformKeychain` over a store
    // that is the device's, or over one that lives in this process. The
    // difference is observable only in that this works at all — the real
    // platform store answers through a native bridge, and there is none here.
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $stack = theFirstStackThisDeviceHolds();
    $kept = whateverIsBoundToSessions()->keep($stack->id(), Session::of('not-a-credential'));

    expect($kept->either(static fn(): object => new stdClass(), static fn(object $why): object => $why))
        ->toBeInstanceOf(stdClass::class);

    // Resumed through a freshly built adapter, which is what proves the three
    // ports share one store rather than each holding its own — the arrangement
    // the device's single keychain actually has.
    $resumed = whateverIsBoundToSessions()->resume($stack->id());

    expect($resumed->either(static fn(): object => new stdClass(), static fn(): object => new RuntimeException()))
        ->toBeInstanceOf(stdClass::class);
});

it('Q-R72 — takes the place of a port without being asked which', function (): void {
    // The half that says a new affordance costs nothing outside this
    // module. Written over whatever the registry holds rather than over
    // `Reaching`, so the day a second stand-in is added this covers it without
    // being edited — which is the claim the requirement actually makes.
    config(['dx.stands_in' => true]);

    app()->register(new DxServiceProvider(app()), force: true);

    $wrong = [];

    foreach (TheStandIns::all() as $standIn) {
        $bound = whatTheContainerHandsBackFor($standIn->insteadOf());

        if (! $bound instanceof ($standIn->which()::class)) {
            $wrong[] = sprintf('%s answers with %s', $standIn->insteadOf(), $bound::class);
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These ports were not taken over by the stand-in that claims them:\n  %s\n\n"
        . 'A stand-in names the port it replaces and the provider binds what it names, so '
        . 'a port answering with something else means two bindings are fighting over it — '
        . "and the container takes the last one and says nothing.\n"
        . 'That is worth failing over here rather than on a device, where it reads as a '
        . 'stand-in that simply does not work (Q-R72).',
        implode("\n  ", $wrong),
    ));
});
