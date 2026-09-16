<?php

declare(strict_types=1);

namespace Modules\Dx\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Modules\Dx\Internal\TheStandIns;
use Override;

/**
 * Where the stand-ins take the place of the real ports, when they are asked to.
 *
 * **It binds nothing unless `dx.stands_in` says so, and that is the whole of
 * the safety here.** This module is a development dependency, so it is
 * installed while the suite runs and Laravel discovers this provider along with
 * every other. A provider that bound unconditionally would point the whole
 * suite at a stand-in — and the suite would go on passing, against payloads no
 * stack ever sent, saying nothing. That is a worse failure than any this module
 * prevents, so the default is off and
 * {@see \Tests\Feature\TheSuiteNeverRunsAgainstAStandInTest} keeps it that way.
 *
 * **It rebinds rather than being bound to.** The composition root is the one
 * place allowed to say which adapter a port gets, and it still is: it binds
 * `Reaching` to `PinnedClients` as it always did, and this runs afterwards and
 * says *not that one, this one* — for exactly as long as somebody asked. The
 * composition root does not name this module and cannot, because in a release
 * there is nothing here to name.
 */
final class DxServiceProvider extends ServiceProvider
{
    /** The switch, off unless something outside the application turned it on. */
    private const string ASKED_FOR = 'dx.stands_in';

    #[Override]
    public function register(): void
    {
        // Inside the callback rather than beside it, and the reason is the
        // distinction `A9` exists to draw: a provider may describe what will
        // happen and may not make it happen. Everything below runs once the
        // application is booted and its configuration is settled — not while an
        // operator is watching a splash screen — so reading a setting and
        // asking the container for one are both fair here, and neither would be
        // a line higher up.
        //
        // It also answers the one thing that has to be true for the switch to
        // work at all: the composition root has bound every port by then, so
        // what follows replaces a binding rather than racing one.
        $this->app->booted(static function (Application $app): void {
            try {
                $settings = $app->make('config');
            } catch (BindingResolutionException) {
                // An application with no configuration is one nobody has asked
                // anything of, and the only safe reading of *nobody said* is
                // *nobody asked*: this module takes over the ports the rest of
                // the app runs on.
                //
                // Caught here rather than in a method of its own, because the
                // analyser refuses a checked exception escaping a closure and
                // refuses a method taking a container — so the one place the
                // catch can sit is inside the closure that holds both.
                return;
            }

            // Compared against `true` rather than cast, because `config/dx.php`
            // has already turned every spelling this value can arrive in into
            // an actual boolean.
            if ($settings->get(self::ASKED_FOR) !== true) {
                return;
            }

            foreach (TheStandIns::all() as $standIn) {
                // Bound rather than made a singleton. `StandsIn::which()`
                // promises a fresh answer per call because the ports it
                // replaces promise one — a client is built for a single stack
                // and carries a single session — and a stand-in that was a
                // singleton where the real one is not would be a difference
                // between what is being looked at and what ships.
                $app->bind(
                    $standIn->insteadOf(),
                    static fn(): object => $standIn->which(),
                );
            }
        });
    }
}
