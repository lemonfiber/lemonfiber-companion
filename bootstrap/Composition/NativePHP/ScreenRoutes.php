<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;
use Illuminate\Support\Facades\Route;

use function is_array;
use function ltrim;

use Native\Mobile\Edge\NativeRouter;

use function request;
use function sprintf;

/**
 * `Route::native()`, building each screen through the container.
 *
 * NativePHP registers a macro of this name whose route closure does
 * `new NativeRouter` — and that router builds a screen with `new $class`, so a
 * screen cannot be given a port. The only alternative is a screen that reaches
 * the container itself, which `A3` refuses and for the reason `A3` gives: a
 * class that reaches the container stops telling the truth about what it needs.
 * {@see ScreenRouter} is the fix, and this is what gets it used — the vendor's
 * closure names its own router directly, so there is no seam to swap.
 *
 * **A macro of the same name replaces the one before it**, so this is a
 * substitution rather than a second spelling. Nothing in the application has to
 * remember to call a differently-named one, and a screen registered through
 * `Route::native()` gets the container whoever wrote it.
 *
 * **The `if` NativePHP puts in its route closure is a {@see Runloop} here.**
 * That guard exists because a suite must never enter the runloop, and as a
 * branch it was also the reason nothing past it could be covered. Whether there
 * is a device to run on is a fact about the composition rather than about
 * routing, so the composition root decides it once — `TheRunloop` on a handset,
 * {@see TheHarnessInstead} in a suite — and this class has no branch at all.
 *
 * Two things the closure this replaces also did are deliberately not here.
 *
 * **A fallback for a native route reached with no native runtime** — a shared
 * link opened in a browser — which the vendor answers through a
 * `NativeRouteFallback` binding. This application binds none and will not:
 * Setup happens at the machine, and there is no web surface here
 * to land on. The contract does not exist in the installed version either, so
 * carrying the branch would mean naming a class that is not there.
 *
 * **Restoring the navigation stack after a hot reload.** That reads
 * `storage/framework/.hot_restart` directly, and `B3` puts the filesystem
 * behind a port for a reason this case does not escape. The cost is named
 * rather than hidden: after saving a file during `native:run`, the app returns
 * to the entry screen instead of where the developer was. It is a development
 * convenience and nothing an operator ever sees; making it work again means a
 * filesystem port and an adapter, not a `file_exists` here.
 *
 * `ScreenRoutesReplaceTheVendorsTest` holds both halves — that the macro is
 * ours, and that the reason it is ours is still true of the installed package.
 *
 * **In `NativePHP/` because that is what it is about.** Everything in this
 * directory exists to work around or to reach into one package, and a reader
 * asking *why is any of this here* gets the answer from the path before they
 * open a file. It is also what makes the day NativePHP closes the gap a clean
 * one: the fix is to delete a directory, not to pick four classes out of the
 * composition root and hope none of them was load-bearing elsewhere.
 */
final readonly class ScreenRoutes
{
    /**
     * @param Closure(string): mixed $build how a screen is made, given its name
     *
     * @param-later-invoked-callable $build The container raises where a name
     * resolves to nothing, and that is a launch-time fatal wherever it is
     * raised. Annotated as deferred rather than caught here: catching it would
     * mean this class deciding what to do about a route that names a class the
     * application does not have, and the honest answer to that is the fatal.
     */
    public function __construct(private Closure $build, private Runloop $runloop) {}

    /**
     * Register the macro, replacing NativePHP's own.
     *
     * Called from the composition root's `boot()` rather than `register()`,
     * because the macro this replaces is added by the NativePHP package's own
     * boot — and provider order between two discovered packages is not
     * something either of them decides.
     *
     * The outer closure is not `static`, which is `Router::macro`'s
     * requirement rather than a style choice: a macro is bound to the router it
     * is called on, and a static closure cannot be bound. The inner one is,
     * because a route closure is called rather than bound and a `$this` nobody
     * uses is a `$this` somebody eventually reaches for.
     */
    public function declare(): void
    {
        $answer = $this->answer(...);

        Route::macro('native', function (string $uri, string $screen) use ($answer): mixed {
            NativeRouter::register($uri, $screen);

            return Route::get($uri, static fn(): mixed => $answer($screen));
        });
    }

    /**
     * What a request for one screen is answered with.
     *
     * A method rather than the body of the route closure, because a macro is
     * *bound* to the router it is called on — so `self::` inside one names
     * `Router`, and a constant of this class read there is a fatal at the
     * moment somebody opens the app. Everything that needs this class stays in
     * a method of it, and the closures carry nothing but a callable.
     */
    private function answer(string $screen): mixed
    {
        $path = sprintf('/%s', ltrim(request()->path(), '/'));

        return $this->runloop->enter($this->build, $screen, $this->parameters($path), $path);
    }

    /**
     * The route parameters this path carries, as the navigation stack reads them.
     *
     * Asked of `NativeRouter` rather than of Laravel's own route, because these
     * are the values the screen will be handed and the two registries are not
     * the same one.
     *
     * @return array<mixed>
     */
    private function parameters(string $path): array
    {
        $resolved = NativeRouter::resolve($path);

        if ($resolved === null) {
            return [];
        }

        $params = $resolved['params'];

        return is_array($params) ? $params : [];
    }
}
