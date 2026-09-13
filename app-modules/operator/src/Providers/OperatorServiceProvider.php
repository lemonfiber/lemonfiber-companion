<?php

declare(strict_types=1);

namespace Modules\Operator\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Operator\Internal\Screens\NoStackYet;
use Modules\Operator\Internal\Screens\PairByTyping;

/**
 * The operator surface, declaring its own screens.
 *
 * `routes/native.php` says this in words — "screens are declared by their
 * surface module's service provider where they belong to a surface" — and this
 * is the first module to do it, so it is also the pattern the other five will
 * follow.
 *
 * The alternative was one file listing every screen in the application. It
 * reads well on the day it is written and it puts the shell in the business of
 * knowing both surfaces, which is the coupling the module boundaries exist to
 * prevent: `operator` and `household` are separate surfaces precisely because
 * neither should be able to see the other's navigation.
 *
 * Discovered by Laravel's package discovery, through `extra.laravel.providers`
 * in this module's own manifest. A module that declares a provider is therefore
 * registered by the same mechanism that registers any package, rather than by a
 * list in the application that would need a line per module.
 */
final class OperatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // The views are not registered here. `internachi/modular` already
        // registers each module's `resources/views` under the module's own name,
        // so `operator::no-stack-yet` resolves without this provider doing
        // anything — and a `loadViewsFrom` beside it looks like the thing that
        // makes the screen work while making no difference at all.
        //
        // Found by the mutation run rather than by reading: removing the call
        // left every test passing, which is what a line that does nothing looks
        // like from the inside.
        //
        // `Router` rather than the `Route` facade, which A2/A4 refuses as a
        // global lookup no constructor mentions. `native()` is the macro the
        // NativePHP package adds — it registers the component with the
        // navigation stack and an ordinary GET route beside it, because the
        // device drives this application through the HTTP kernel.
        //
        // Called statically because that is what it is. `Route::macro()` stores
        // the closure in `Router::$macros`, and the closure uses no `$this` —
        // it reaches `NativeRouter::register()` and `Route::get()` and nothing
        // else — so `__callStatic` binds it to null and runs it unchanged. An
        // injected router would read better and the analyser refuses it: it
        // knows the macro only as a static method, and calling a static method
        // on an instance is a rule this repository leaves on for its own code.
        //
        // After every provider has booted, because the macro is added by the
        // NativePHP package's own boot and provider order between two discovered
        // packages is not something either of them decides. Declared in `boot()`
        // directly, this throws "Router::native does not exist" — a real failure
        // at launch rather than a missing screen, so at least it is loud.
        //
        // `$this->app->booted()` and not `$this->booted()`, which reads like the
        // same thing and is not: a provider's own booted callbacks fire at the
        // end of *its* boot, not at the end of everybody's. The two differ only
        // when another provider has something you need, which is exactly the
        // case here and exactly the case where the difference is invisible until
        // it fails.
        $this->app->booted(static function (): void {
            Router::native('/', NoStackYet::class);
            // The typed road (N1-R6, N4-R3). Its own URI rather than a mode of
            // the entry screen, because the navigation stack is what lets an
            // operator back out of it — and because a screen that pairs is one
            // #[Concealed] has to be able to name, which it cannot do for half
            // of another screen.
            Router::native('/pair/typed', PairByTyping::class);
        });
    }
}
