<?php

declare(strict_types=1);

use Modules\Dx\Api\ClientsThatReachNothing;
use Modules\Dx\Internal\TheStandIns;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Reaching;
use Modules\Sdk\Api\PinnedClients;

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
function theConfigurationTheApplicationHolds(): object
{
    return whatTheContainerHandsBackFor('config');
}

function whatTheContainerHandsBackFor(string $port): object
{
    $built = app()->make($port);

    return is_object($built)
        ? $built
        : throw new RuntimeException(sprintf('The container answered %s with something that is not an object.', $port));
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

it('Q-R72 — takes the place of a port without being asked which', function (): void {
    // The half of `Q-R72` that says a new affordance costs nothing outside this
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
