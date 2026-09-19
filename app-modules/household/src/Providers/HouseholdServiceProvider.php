<?php

declare(strict_types=1);

namespace Modules\Household\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Household\Internal\Screens\WhatYouAreOwed;
use Modules\Stacks\Api\AStacksScreen;

/**
 * The household surface, declaring its own screens.
 *
 * Screens are declared by their surface module's service provider where they
 * belong to a surface, which is what keeps one file from having to know both
 * surfaces' navigation — the coupling the module boundaries exist to prevent.
 *
 * The path comes from {@see AStacksScreen}, which is in a capability module
 * because both surfaces name it: this one registers the member's reading and
 * the operator's surface offers the way in, and neither may name the other. A
 * path spelled here and again there would be a button pointing at something
 * nothing serves, on a handset, with no error anywhere.
 *
 * Discovered by Laravel's package discovery, through `extra.laravel.providers`
 * in this module's own manifest.
 */
final class HouseholdServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // The views are not registered here. `internachi/modular` already
        // registers each module's `resources/views` under the module's own
        // name, so `household::what-you-are-owed` resolves without this
        // provider doing anything.
        //
        // `Router` rather than the `Route` facade, which `A2`/`A4` refuses as a
        // global lookup no constructor mentions. `native()` is the macro the
        // NativePHP package adds, called statically because that is what it is.
        //
        // After every provider has booted, because the macro is added by the
        // NativePHP package's own boot and provider order between two
        // discovered packages is not something either of them decides.
        // `$this->app->booted()` and not `$this->booted()`, which reads like
        // the same thing and fires at the end of this provider's boot rather
        // than at the end of everybody's.
        $this->app->booted(static function (): void {
            Router::native(AStacksScreen::Owed->value, WhatYouAreOwed::class);
        });
    }
}
