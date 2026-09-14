<?php

declare(strict_types=1);

namespace Modules\Operator\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\PairByScanning;
use Modules\Operator\Internal\Screens\PairByTyping;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Operator\Internal\Screens\WhatTheHouseholdAsked;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Operator\Internal\Screens\YourStacks;

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
        // so `operator::your-stacks` resolves without this provider doing
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
            Router::native(AScreenWithoutAStack::TheList->value, YourStacks::class);
            // The two roads N1-R6 requires, each its own URI rather than a
            // mode of the entry screen: the navigation stack is what lets an
            // operator back out of one, and a screen that pairs is one
            // #[Concealed] has to be able to name — which it cannot do for half
            // of another screen.
            //
            // Two screens rather than one with a switch, because they are not
            // the same flow with a different input widget. ADR-0018 puts a
            // software comparison on the scanned road and N1-R50 puts a person
            // on the typed one, so one of them has a confirmation step and the
            // other must not be able to reach one.
            Router::native(AScreenWithoutAStack::PairByScanning->value, PairByScanning::class);
            Router::native(AScreenWithoutAStack::PairByTyping->value, PairByTyping::class);

            // The stack in the URI rather than in the screen, because `N1-R11`
            // keeps each stack's session separate and a screen that chose its
            // own stack is the place two of them come to share one. It is also
            // what makes the navigation stack correct: an operator backing out
            // of this screen returns to the list they picked from, and a second
            // stack signed into later is a second entry rather than the same
            // screen re-pointed.
            Router::native(AStacksScreen::SignIn->value, SignIntoAStack::class);

            // What the house asked for, which is the part of a stack an
            // operator gets asked about in person. Before `/stacks/{stack}`
            // rather than after it, for the reason `sign-in` is: the router
            // matches in registration order, and a bare `{stack}` registered
            // first would swallow every path under it.
            Router::native(AStacksScreen::Requests->value, WhatTheHouseholdAsked::class);

            // What the machine would put right, stated before anybody is asked
            // to agree (`N2-R4`). A screen rather than a dialog behind a
            // button: a sentence an operator has to tap to reveal is one they
            // will agree without reading.
            Router::native(AStacksScreen::Repairs->value, WhatWouldBePutRight::class);

            // What the whole application is for: one stack, and whether it is
            // doing what it should. A screen of its own rather than a section
            // of the list, because `N1-R17` says the app asks a machine once
            // per screen — a list that reported on every stack would ask every
            // machine on the network to draw one frame.
            Router::native(AStacksScreen::Health->value, HowThisStackIs::class);
        });
    }
}
