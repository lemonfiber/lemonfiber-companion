<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Native\Mobile\Edge\NativeRouter;

// The app answers one kind of request: the device asking for a screen.
//
// Everything reaches PHP through the device, as a request for a screen. A route
// that takes anything else — a POST that builds a class from its body, one that
// calls a bridge function by name, a PUT that writes a file — is a door that a
// page in a web view could walk through, and this app renders no web view to
// need one. So the router holds screens and nothing else.

/**
 * Every route the application registers.
 *
 * A named function because resolving the router raises, and the analyser
 * refuses a checked exception inside a closure.
 *
 * @return list<Route>
 */
function everyRouteTheAppRegisters(): array
{
    return array_values(app()->make(Router::class)->getRoutes()->getRoutes());
}

/** @return list<string> every route that is not a request for a screen */
function everyRouteThatIsNotAScreen(): array
{
    $doors = [];

    foreach (everyRouteTheAppRegisters() as $route) {
        /** @var list<string> $methods */
        $methods = $route->methods();
        $path = sprintf('/%s', ltrim($route->uri(), '/'));

        if (array_diff($methods, ['GET', 'HEAD']) !== [] || ! NativeRouter::isNativeRoute($path)) {
            $doors[] = sprintf('%s %s', implode('|', $methods), $route->uri());
        }
    }

    return $doors;
}

it('holds no route but a screen, so nothing can be posted, put or called', function (): void {
    expect(everyRouteTheAppRegisters())->not->toBe([])
        ->and(everyRouteThatIsNotAScreen())->toBe([]);
});

it('no longer carries the routes that built a class or called the bridge by name', function (): void {
    $paths = array_map(static fn(Route $route): string => $route->uri(), everyRouteTheAppRegisters());

    expect($paths)->not->toContain('_native/api/events')
        ->and($paths)->not->toContain('_native/api/call')
        ->and($paths)->not->toContain('storage/{path}');
});
