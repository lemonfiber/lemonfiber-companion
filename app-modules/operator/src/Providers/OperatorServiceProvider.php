<?php

declare(strict_types=1);

namespace Modules\Operator\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowFullThisMachineIs;
use Modules\Operator\Internal\Screens\HowTheLineIsSharedHere;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\PairByScanning;
use Modules\Operator\Internal\Screens\PairByTyping;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Operator\Internal\Screens\WhatElseIsRunningHere;
use Modules\Operator\Internal\Screens\WhatIsRunningHere;
use Modules\Operator\Internal\Screens\WhatItHoldsToLetThemIn;
use Modules\Operator\Internal\Screens\WhatKeepsRunningHere;
use Modules\Operator\Internal\Screens\WhatLeavesHere;
use Modules\Operator\Internal\Screens\WhatStoppedComingIn;
use Modules\Operator\Internal\Screens\WhatTheHouseholdAsked;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\Screens\WhatThisMachineKeepsHere;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWasChangedHere;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Operator\Internal\Screens\WhatYouAreToldAbout;
use Modules\Operator\Internal\Screens\WhereTheHouseholdComesIn;
use Modules\Operator\Internal\Screens\WhereThisComesFrom;
use Modules\Operator\Internal\Screens\WhereThisGotTo;
use Modules\Operator\Internal\Screens\WhichAppToWatchOn;
use Modules\Operator\Internal\Screens\YourStacks;
use Modules\Stacks\Api\AStacksScreen;

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
            // The two roads required, each its own URI rather than a
            // mode of the entry screen: the navigation stack is what lets an
            // operator back out of one, and a screen that pairs is one
            // #[Concealed] has to be able to name — which it cannot do for half
            // of another screen.
            //
            // Two screens rather than one with a switch, because they are not
            // the same flow with a different input widget. ADR-0018 puts a
            // software comparison on the scanned road and a person
            // on the typed one, so one of them has a confirmation step and the
            // other must not be able to reach one.
            Router::native(AScreenWithoutAStack::PairByScanning->value, PairByScanning::class);
            Router::native(AScreenWithoutAStack::PairByTyping->value, PairByTyping::class);

            // The stack in the URI rather than in the screen, because a device
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
            // to agree. A screen rather than a dialog behind a
            // button: a sentence an operator has to tap to reveal is one they
            // will agree without reading.
            Router::native(AStacksScreen::Repairs->value, WhatWouldBePutRight::class);

            // What has stopped coming in, which is the first of four.
            // A screen of its own rather than a section of the health one: a
            // stack passing every check and a household getting nothing are not
            // a contradiction, and folding this into health would put the two
            // under one verdict that has to be about one of them.
            Router::native(AStacksScreen::Stuck->value, WhatStoppedComingIn::class);
            Router::native(AStacksScreen::Updates->value, HowCurrentThisStackIs::class);

            // What this machine is running, and the three verbs about it
            // A screen of its own rather than a section of health:
            // health answers *is anything wrong*, and this answers *what is on
            // and what do I want on*, which an operator opens the app for even
            // when every check passes.
            Router::native(AStacksScreen::Services->value, WhatThisStackRuns::class);

            // What the machine keeps running with nobody signed in. Beside the
            // services listing rather than inside it, because the two answer
            // different questions about the same machine: that one says what is
            // running now, and this says what would still be running after a
            // reboot nobody was there for.
            Router::native(AStacksScreen::Hosting->value, WhatKeepsRunningHere::class);

            // Everything the stack is set to. Beside the services listing
            // rather than under one of them, because a setting is the
            // machine's and not a service's — and an operator looking for one
            // does not know, and should not have to guess, which service owns
            // it.
            Router::native(AStacksScreen::Settings->value, WhatThisStackIsSetTo::class);

            // One of the things it runs, and the verbs about that one
            // Split out of the listing above rather than drawn on
            // it: a stack running four services put fifteen controls on one
            // frame, four of them named *Start it*, and which one a control
            // acted on was carried by where it sat. A list is read, and a verb
            // is chosen — two acts, and the frame each wants is not the same
            // frame.
            //
            // It serves a form as well, under the same path. What the operator
            // is choosing between is identical and what the stack is told
            // differs only in which name it carries, so a second screen would
            // be the same screen with one word changed.
            Router::native(AStacksScreen::Doing->value, WhatToDoWithThis::class);

            // What is running here that this machine never declared.
            // Separate from the listing above rather than a section of it,
            // because that screen offers a verb against every row and the
            // requirement forbids offering one against these — two screens is
            // the version of that refusal which survives somebody adding a row.
            Router::native(AStacksScreen::Elsewhere->value, WhatElseIsRunningHere::class);

            // What one service has been saying. Two placeholders,
            // which no other route here has: the service is in the path rather
            // than held by the screen, so a frame whose URI names one service
            // cannot be showing another's lines under its heading.
            Router::native(AStacksScreen::Logs->value, WhatThisServiceSaid::class);

            // What the machine has changed about itself. Its own screen rather
            // than a section of the settings, because a setting is how the
            // machine stands now and this is how it came to stand there.
            Router::native(AStacksScreen::Record->value, WhatWasChangedHere::class);

            // Where every service comes from. Its own screen rather than a
            // section of the record, because the record is what was done and
            // this is what it was done with — two questions asked at different
            // moments.
            Router::native(AStacksScreen::Origins->value, WhereThisComesFrom::class);

            // Everything that leaves the machine. Its own screen, because what
            // it answers is a privacy question asked apart from the others,
            // and it keeps lemonfiber's connections and the services' in two
            // lists that no other screen would have room to keep apart.
            Router::native(AStacksScreen::Leaving->value, WhatLeavesHere::class);

            // What the operator will be told about. Its own screen, because
            // *will this wake me* is asked before a night away and not while
            // reading what the machine sends.
            Router::native(AStacksScreen::Told->value, WhatYouAreToldAbout::class);

            // How the line is shared. Its own screen, because *why is the
            // internet slow* is asked by the household at the moment it is slow.
            Router::native(AStacksScreen::Line->value, HowTheLineIsSharedHere::class);

            // What it keeps on the machine and the copies it holds: two
            // readings on one screen.
            Router::native(AStacksScreen::Keeps->value, WhatThisMachineKeepsHere::class);

            // How full it is. Its own screen, because a warning that the disk
            // is filling arrives on its own.
            Router::native(AStacksScreen::Room->value, HowFullThisMachineIs::class);

            // Which version of lemonfiber runs, apart from the services' updates.
            Router::native(AStacksScreen::Itself->value, WhatIsRunningHere::class);

            // Who gets in: what it holds to let services in, which app the
            // household watches on, and where they come in. Three screens,
            // because each is its own reading and its own question.
            Router::native(AStacksScreen::Credentials->value, WhatItHoldsToLetThemIn::class);
            Router::native(AStacksScreen::Clients->value, WhichAppToWatchOn::class);
            Router::native(AStacksScreen::FrontDoor->value, WhereTheHouseholdComesIn::class);

            // What its words mean, which every other screen uses.
            Router::native(AStacksScreen::Words->value, WhatTheWordsMean::class);
            // A word can hold a slash, which the builder encodes and the
            // router decodes before matching, so the segment takes the rest.
            Router::native(AStacksScreen::WordAbout->value, WhatTheWordsMean::class)->where('service', '.+');

            // Where one item got to, from whatever named it. A title can hold
            // a slash, so the segment takes the rest, as a word's does.
            Router::native(AStacksScreen::Trace->value, WhereThisGotTo::class)->where('service', '.+');

            // What the whole application is for: one stack, and whether it is
            // doing what it should. A screen of its own rather than a section
            // of the list, because the app reads a machine once
            // per frame — a list that reported on every stack would ask every
            // machine on the network to draw one frame.
            Router::native(AStacksScreen::Health->value, HowThisStackIs::class);
        });
    }
}
