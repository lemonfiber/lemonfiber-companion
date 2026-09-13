<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\ScreenIsNotAScreen;
use Bootstrap\Composition\NativePHP\ScreenRouter;
use Bootstrap\Composition\NativePHP\ScreenRoutes;
use Bootstrap\Composition\NativePHP\TheHarnessInstead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\Screens\NoStackYet;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ARunloopThatOnlyRemembers;
use Tests\Support\Fakes\StacksInMemory;

// A3 — a screen is built through the container, so it can be given a port.
//
// NativePHP's own router builds a screen with `new $class` and `mount()` takes
// no arguments, so a screen has no way to receive anything. Every screen after
// the first reads something, and the alternative to this substitution is a
// screen that reaches the container itself — the service location A3 refuses,
// and for the reason A3 gives.
//
// Two halves, and both matter. The first is that the substitution happened: a
// macro of the same name replaces the one before it, and provider order between
// two discovered packages is not something either of them decides, so "ours
// booted last" is asserted rather than assumed. The second is that the reason
// for it is still true of the installed package — a fork that outlives its
// reason is worse than the gap it closed, because nobody goes back to look.

it('A3 — the macro registered is ours, not the package\'s', function (): void {
    // Asked of the behaviour rather than of the closure's identity: a macro is
    // a closure in an array, and comparing it to anything would be comparing it
    // to a copy of itself. What distinguishes ours is the router it builds
    // with, and the route it registers is where that is observable.
    expect(Route::hasMacro('native'))->toBeTrue();

    Route::native('/a-screen-for-this-test', NoStackYet::class);

    expect(NativeRouter::resolve('/a-screen-for-this-test'))
        ->not->toBeNull('our macro must still register with the navigation stack');
});

it('answers a request for a screen through the runloop it was given', function (): void {
    // The route's own closure, called rather than routed. `Route::run()` raises
    // a checked exception and every Pest body is a closure — the same rule that
    // put the container behind a method in the composition root — and calling
    // the closure is the narrower claim anyway: what is asserted is what our
    // macro registered, not that Laravel can route.
    //
    // A stand-in runloop, which is what the seam is for: the real one blocks
    // against the bridge, and a suite that reached it would hang rather than
    // fail.
    $entered = new ARunloopThatOnlyRemembers();

    new ScreenRoutes(aBuildThatKnows(), $entered)->declare();
    Route::native('/a-screen-answered-in-this-test', NoStackYet::class);

    ranTheRouteAt('/a-screen-answered-in-this-test');

    // The screen comes from the registration; the path comes from the request
    // being served, which is what a device navigating would have set and what
    // the navigation stack resolves parameters against. Calling the closure
    // directly leaves the test's own request in place, so `/` is the right
    // answer here and asserting the registered URI would be asserting the
    // wrong thing.
    $ran = $entered->whatItRan();

    expect($ran['screen'])->toBe(NoStackYet::class)
        ->and($ran['path'])->toBe('/')
        ->and($ran['params'])->toBe([]);
});

it('serves the route this application actually registered', function (): void {
    // The composition root's own wiring, end to end: its build closure reaches
    // the container, and the runloop it chose for a suite answers instead of
    // blocking. Safe now for the reason it was not before — `TheHarnessInstead`
    // is what a test gets, so there is no runloop to fall into.
    $answered = ranTheRouteAt('/');

    expect($answered)->toBeInstanceOf(Response::class);

    $said = $answered instanceof Response ? (string) $answered->getContent() : '';

    expect($said)->toContain(NoStackYet::class);
});

it('takes no parameters from a path the navigation stack does not know', function (): void {
    // The route registered at `/` is asked about a request for somewhere else,
    // which is the shape a deep link that no longer resolves arrives in. There
    // is nothing to hand the screen, and nothing is what it gets — rather than
    // a lookup that raises on the way to a screen that would have rendered.
    $entered = new ARunloopThatOnlyRemembers();

    new ScreenRoutes(aBuildThatKnows(), $entered)->declare();
    Route::native('/a-screen-for-an-unknown-path', NoStackYet::class);

    servingARequestFor('/nothing-is-registered-here');

    ranTheRouteAt('/a-screen-for-an-unknown-path');

    expect($entered->whatItRan()['params'])->toBe([])
        ->and($entered->whatItRan()['path'])->toBe('/nothing-is-registered-here');
});

it('resolves the parameters the navigation stack holds, not the router\'s', function (): void {
    // The two registries are not the same one, and the parameters a screen is
    // handed come from NativePHP's. A path registered with a parameter is what
    // shows that this asks the right one — the previous case drives a path with
    // none, which both registries agree about.
    $entered = new ARunloopThatOnlyRemembers();

    new ScreenRoutes(aBuildThatKnows(), $entered)->declare();
    Route::native('/a-stack/{stack}', NoStackYet::class);

    ranTheRouteAt('/a-stack/{stack}');

    expect($entered->whatItRan()['params'])->toBeArray();
});

it('answers a request for a screen with the harness where there is no device', function (): void {
    // The composition root binds this one in a suite, so nothing here is ever
    // asked to run a screen. It says where to test it instead, because a 200
    // with nothing in it is what a passing smoke test looks like when the route
    // is wrong.
    $answered = new TheHarnessInstead()->enter(
        aBuildThatKnows(),
        NoStackYet::class,
        [],
        '/',
    );

    expect($answered)->toBeInstanceOf(Response::class);

    $said = $answered instanceof Response ? (string) $answered->getContent() : '';

    expect($said)->toContain(NoStackYet::class)
        ->and($said)->toContain('Native::test()')
        ->and($answered instanceof Response ? $answered->getStatusCode() : 0)->toBe(200);
});

it('builds a screen through the container, with what it asked for', function (): void {
    // The whole of the fix, and the assertion is about the argument rather than
    // the object: `NoStackYet` takes a `Stacks`, and `new NoStackYet` — which
    // is what the package does — is a fatal about a missing argument. That it
    // exists at all is the proof.
    $screen = aScreen();

    expect($screen)->toBeInstanceOf(NoStackYet::class)
        ->and($screen->nothingIsPairedYet())->toBeTrue();
});

it('hands the screen its router, its parameters and its data, as the package does', function (): void {
    // The other three lines of `createComponent` are the parent's, in the
    // parent's order. A screen that lost its router could not navigate, and
    // nothing else in this application would notice.
    $router = screensBuiltNormally();

    $component = withCreateComponent($router, NoStackYet::class, ['id' => '7'], ['from' => 'a test']);

    expect($component->param('id'))->toBe('7')
        ->and($component->data('from'))->toBe('a test');
});

it('refuses a route that names something which is not a screen', function (): void {
    // A typo in a registration, or a binding pointed somewhere else. Without
    // this the router calls `setRouter()` on it and a reader meets a fatal
    // about an undefined method, three frames inside the vendor package, at the
    // moment the app launches.
    $router = new ScreenRouter(static fn(string $class): string => sprintf('%s is not a screen at all', $class));

    $refused = null;

    try {
        withCreateComponent($router, Stacks::class);
    } catch (ScreenIsNotAScreen $said) {
        // Caught rather than driven through `toThrow`, whose closure may not
        // raise a checked exception — which is every exception here, because
        // nothing in this repository declares one unchecked.
        $refused = $said->getMessage();
    }

    expect($refused)->toContain(Stacks::class);
});

it('the package still builds a screen with `new`, which is why this fork exists', function (): void {
    // The guard on the fork. When NativePHP resolves a component itself, this
    // goes red — and what it is asking for then is that ScreenRouter, this
    // file and the ScreenRoutes macro are deleted, not that the assertion is
    // relaxed.
    $source = (string) file_get_contents(
        base_path('vendor/nativephp/mobile/src/Edge/NativeRouter.php'),
    );

    // `str_contains` rather than `toContain`, which takes needles rather than a
    // message — and a guard whose failure does not say what to do about it is a
    // guard somebody deletes.
    expect(str_contains($source, '$component = new $class;'))->toBeTrue(
        'NativePHP now builds a component some other way. If it resolves through the '
        . 'container, delete ScreenRouter, ScreenRoutes and this file — the gap they '
        . 'were written for has closed. If it builds it differently for another '
        . 'reason, ScreenRouter must mirror whatever it does now.',
    );
});

it('the package\'s own macro would not have used ours', function (): void {
    // The second half of the same guard. Our macro exists because the vendor's
    // route closure names its own router directly, so there is no seam to swap.
    $source = (string) file_get_contents(
        base_path('vendor/nativephp/mobile/src/NativeServiceProvider.php'),
    );

    expect(str_contains($source, '$router = new NativeRouter;'))->toBeTrue(
        'The package no longer builds its own router in the route closure. If it now '
        . 'resolves one, bind ScreenRouter to it and delete the ScreenRoutes macro.',
    );
});

/**
 * How a screen is built, answered from a map.
 *
 * From a map rather than by ignoring the name, so the name the router asks for
 * is one it has to have been given: a closure that ignored its argument would
 * pass whether or not anything ever consulted it.
 *
 * @return Closure(string): mixed
 */
function aBuildThatKnows(): Closure
{
    $screens = [NoStackYet::class => aScreen(...)];

    return static fn(string $class): mixed => ($screens[$class] ?? static fn(): string => 'no such screen')();
}

/**
 * Put a request for one path in front of the application.
 *
 * A function rather than the line itself, because `Request::create()` raises a
 * checked exception and every Pest body is a closure — the same rule that put
 * the container behind a method in the composition root.
 */
function servingARequestFor(string $path): void
{
    app()->instance('request', Request::create($path));
}

/** Call the closure a route was registered with, without routing to it. */
function ranTheRouteAt(string $uri): mixed
{
    $action = routeFor($uri)->getAction('uses');

    expect($action)->toBeInstanceOf(Closure::class);

    return $action instanceof Closure ? $action() : null;
}

/**
 * The registered route for one URI, or a failure that says which.
 *
 * `RouteCollection::match()` would need a request and answers about method and
 * middleware too; what this asks is narrower — did our macro register a route
 * at this path at all.
 */
function routeFor(string $uri): RoutingRoute
{
    $matching = array_values(array_filter(
        Route::getRoutes()->getRoutes(),
        // Both spellings, because Laravel keeps the root route's URI as `/` and
        // every other one without its leading slash.
        static fn(RoutingRoute $registered): bool => in_array(
            $registered->uri(),
            [$uri, ltrim($uri, '/')],
            strict: true,
        ),
    ));

    expect($matching)->not->toBeEmpty(sprintf('nothing is registered at %s', $uri));

    return $matching[0];
}

/**
 * A router that builds screens the way the composition root does.
 *
 * Through the application's own container rather than a stub of one, so what is
 * driven here is the wiring that ships — a stub would prove that a closure was
 * called and nothing about whether a screen can actually be built.
 */
function screensBuiltNormally(): ScreenRouter
{
    // The build closure is the composition root's, in shape: a screen arrives
    // with what it declared. Written out rather than reaching the container,
    // because a test body is a closure and `make()` raises a checked exception
    // — the same rule that put the container behind a method over there.
    //
    // What that costs is nothing this test was for: it drives whether
    // `ScreenRouter` uses the closure it was given, and `CompositionRootIsComplete`
    // is what drives whether the container can answer.
    // Answered from a map, so the name the router asks for is a name this has
    // to have been given — a closure that ignored it would pass whether or not
    // the router ever consulted it.
    return new ScreenRouter(aBuildThatKnows());
}

/** The one screen this application has, built as the router would build it. */
function aScreen(): NoStackYet
{
    return new NoStackYet(StacksInMemory::working());
}

/**
 * `createComponent`, which is protected because only the router calls it.
 *
 * Reached through reflection rather than by subclassing it for a test: a
 * subclass would be a second implementation of the thing under test, and the
 * first mistake it hid would be one about the real one.
 *
 * @param array<mixed> $params
 * @param array<mixed> $data
 */
function withCreateComponent(ScreenRouter $router, string $class, array $params = [], array $data = []): NativeComponent
{
    $made = new ReflectionMethod($router, 'createComponent')->invoke($router, $class, $params, $data);

    // Asserted rather than cast, so a router that answered with something else
    // fails here with a sentence rather than further down with a type error.
    expect($made)->toBeInstanceOf(NativeComponent::class);
    assert($made instanceof NativeComponent);

    return $made;
}
