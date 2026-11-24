<?php

declare(strict_types=1);

namespace Modules\Operator\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Operator\Internal\Screens\AskingForHelpHere;
use Modules\Operator\Internal\Screens\AskingSomebodyIn;
use Modules\Operator\Internal\Screens\ChoosingHowGood;
use Modules\Operator\Internal\Screens\ConnectingADeviceForThem;
use Modules\Operator\Internal\Screens\GivingRoomBack;
use Modules\Operator\Internal\Screens\GuardingWhileYouWatch;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowFullThisMachineIs;
use Modules\Operator\Internal\Screens\HowTheLineIsSharedHere;
use Modules\Operator\Internal\Screens\HowTheServicesAreWired;
use Modules\Operator\Internal\Screens\HowThisPhoneIsSet;
use Modules\Operator\Internal\Screens\HowThisPluginIsSet;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIsServed;
use Modules\Operator\Internal\Screens\JoiningAHouse;
use Modules\Operator\Internal\Screens\LettingADownloadGo;
use Modules\Operator\Internal\Screens\Locked;
use Modules\Operator\Internal\Screens\OneWayAlertsGo;
use Modules\Operator\Internal\Screens\PairByScanning;
use Modules\Operator\Internal\Screens\PairByTyping;
use Modules\Operator\Internal\Screens\PairingAPhone;
use Modules\Operator\Internal\Screens\PausingTheDownloads;
use Modules\Operator\Internal\Screens\PuttingACopyBack;
use Modules\Operator\Internal\Screens\PuttingThatRunBack;
use Modules\Operator\Internal\Screens\PuttingTheConfigurationBack;
use Modules\Operator\Internal\Screens\SigningInByACode;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Operator\Internal\Screens\TakingACopyHere;
use Modules\Operator\Internal\Screens\TakingItOffThisMachine;
use Modules\Operator\Internal\Screens\TakingSomebodyOut;
use Modules\Operator\Internal\Screens\TheKeysAppsHold;
use Modules\Operator\Internal\Screens\TheOutsideWatcher;
use Modules\Operator\Internal\Screens\TheQueueHealingItself;
use Modules\Operator\Internal\Screens\TheScheduledRuns;
use Modules\Operator\Internal\Screens\ThisStackOnThisPhone;
use Modules\Operator\Internal\Screens\WatchingOneArrive;
use Modules\Operator\Internal\Screens\WhatAcceptingItCosts;
use Modules\Operator\Internal\Screens\WhatEachServiceIsFor;
use Modules\Operator\Internal\Screens\WhatEachStackMayTellThisPhone;
use Modules\Operator\Internal\Screens\WhatElseIsRunningHere;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Modules\Operator\Internal\Screens\WhatIsAlreadyOnThisMachine;
use Modules\Operator\Internal\Screens\WhatIsLeftAloneHere;
use Modules\Operator\Internal\Screens\WhatIsNewOnEveryStack;
use Modules\Operator\Internal\Screens\WhatIsPlayingNow;
use Modules\Operator\Internal\Screens\WhatIsRunningHere;
use Modules\Operator\Internal\Screens\WhatItHoldsToLetThemIn;
use Modules\Operator\Internal\Screens\WhatKeepsRunningHere;
use Modules\Operator\Internal\Screens\WhatLeavesHere;
use Modules\Operator\Internal\Screens\WhatRunningACheckDisturbs;
use Modules\Operator\Internal\Screens\WhatRunningChecksDisturbs;
use Modules\Operator\Internal\Screens\WhatSetupDecided;
use Modules\Operator\Internal\Screens\WhatStoppedComingIn;
use Modules\Operator\Internal\Screens\WhatTheHouseholdAsked;
use Modules\Operator\Internal\Screens\WhatTheHouseholdIsAllowed;
use Modules\Operator\Internal\Screens\WhatTheStackItselfReads;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\Screens\WhatThisMachineKeepsHere;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisServiceWaitsFor;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatThisStackWasSetUpWith;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWasChangedHere;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Operator\Internal\Screens\WhatYouAreToldAbout;
use Modules\Operator\Internal\Screens\WhereAlertsGoHere;
use Modules\Operator\Internal\Screens\WhereTheHouseholdComesIn;
use Modules\Operator\Internal\Screens\WhereThisComesFrom;
use Modules\Operator\Internal\Screens\WhereThisGotTo;
use Modules\Operator\Internal\Screens\WhichAppToWatchOn;
use Modules\Operator\Internal\Screens\WhichVersionsRunHere;
use Modules\Operator\Internal\Screens\WhileNobodyWasLooking;
use Modules\Operator\Internal\Screens\YourStacks;
use Modules\Operator\Internal\WhereAStackOpens;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Override;

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
    /** What the one thing a screen is about may be called, a slash included. */
    private const string ITS_NAME_MAY_HOLD_A_SLASH = '.+';

    /** One opening a run: the container holds the one that remembers it has landed. */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(WhereAStackOpens::class);
    }

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
            Router::native(AScreenWithoutAStack::PairByScanning->value, PairByScanning::class);
            Router::native(AScreenWithoutAStack::PairByTyping->value, PairByTyping::class);
            Router::native(AScreenWithoutAStack::JoiningAHouse->value, JoiningAHouse::class);

            Router::native(AScreenWithoutAStack::WhatsNew->value, WhatIsNewOnEveryStack::class);

            Router::native(AScreenWithoutAStack::Locked->value, Locked::class);

            Router::native(AScreenWithoutAStack::Settings->value, HowThisPhoneIsSet::class);

            Router::native(AScreenWithoutAStack::Notifications->value, WhatEachStackMayTellThisPhone::class);

            Router::native(AStacksScreen::SignInByCode->value, SigningInByACode::class);
            Router::native(AStacksScreen::SignIn->value, SignIntoAStack::class);

            // What the house asked for, which is the part of a stack an
            // operator gets asked about in person. Before `/stacks/{stack}`
            // rather than after it, for the reason `sign-in` is: the router
            // matches in registration order, and a bare `{stack}` registered
            // first would swallow every path under it.
            Router::native(AStacksScreen::Requests->value, WhatTheHouseholdAsked::class);

            Router::native(AStacksScreen::Allowance->value, WhatTheHouseholdIsAllowed::class);

            Router::native(AStacksScreen::Repairs->value, WhatWouldBePutRight::class);

            Router::native(AStacksScreen::Stuck->value, WhatStoppedComingIn::class);
            Router::native(AStacksScreen::Updates->value, HowCurrentThisStackIs::class);

            Router::native(AStacksScreen::Services->value, WhatThisStackRuns::class);

            Router::native(AStacksScreen::Hosting->value, WhatKeepsRunningHere::class);
            Router::native(AStacksScreen::WhileNobodyWasLooking->value, WhileNobodyWasLooking::class);
            Router::native(AStacksScreen::Watcher->value, TheOutsideWatcher::class);
            Router::native(AStacksScreen::Queue->value, TheQueueHealingItself::class);
            Router::native(AStacksScreen::Scheduled->value, TheScheduledRuns::class);
            Router::native(AStacksScreen::Settled->value, WhatThisStackWasSetUpWith::class);
            Router::native(AStacksScreen::Decided->value, WhatSetupDecided::class);
            Router::native(AStacksScreen::Served->value, HowThisStackIsServed::class);

            Router::native(AStacksScreen::Guard->value, GuardingWhileYouWatch::class);

            Router::native(AStacksScreen::Settings->value, WhatThisStackIsSetTo::class);
            Router::native(AStacksScreen::OwnSettings->value, WhatTheStackItselfReads::class);
            Router::native(AStacksScreen::LeftAlone->value, WhatIsLeftAloneHere::class);

            Router::native(AStacksScreen::OnThisPhone->value, ThisStackOnThisPhone::class);

            Router::native(AStacksScreen::Doing->value, WhatToDoWithThis::class);

            Router::native(AStacksScreen::Elsewhere->value, WhatElseIsRunningHere::class);

            Router::native(AStacksScreen::Logs->value, WhatThisServiceSaid::class);

            Router::native(AStacksScreen::Waiting->value, WhatThisServiceWaitsFor::class);

            Router::native(AStacksScreen::Record->value, WhatWasChangedHere::class);

            Router::native(AStacksScreen::RunBack->value, PuttingThatRunBack::class);

            Router::native(AStacksScreen::Origins->value, WhereThisComesFrom::class);

            Router::native(AStacksScreen::Catalogue->value, WhatEachServiceIsFor::class);

            Router::native(AStacksScreen::Leaving->value, WhatLeavesHere::class);

            Router::native(AStacksScreen::Told->value, WhatYouAreToldAbout::class);
            Router::native(AStacksScreen::Alerts->value, WhereAlertsGoHere::class);
            Router::native(AStacksScreen::AlertBackEnd->value, OneWayAlertsGo::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);
            Router::native(AStacksScreen::Playing->value, WhatIsPlayingNow::class);

            Router::native(AStacksScreen::Line->value, HowTheLineIsSharedHere::class);

            Router::native(AStacksScreen::Keeps->value, WhatThisMachineKeepsHere::class);

            Router::native(AStacksScreen::Copy->value, TakingACopyHere::class);
            Router::native(AStacksScreen::PutBack->value, PuttingACopyBack::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);

            Router::native(AStacksScreen::Help->value, AskingForHelpHere::class);

            Router::native(AStacksScreen::Room->value, HowFullThisMachineIs::class);

            Router::native(AStacksScreen::LetGo->value, LettingADownloadGo::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);
            Router::native(AStacksScreen::GiveBack->value, GivingRoomBack::class);
            Router::native(AStacksScreen::Downloads->value, PausingTheDownloads::class);

            Router::native(AStacksScreen::Itself->value, WhatIsRunningHere::class);
            Router::native(AStacksScreen::Versions->value, WhichVersionsRunHere::class);

            Router::native(AStacksScreen::Credentials->value, WhatItHoldsToLetThemIn::class);
            Router::native(AStacksScreen::Keys->value, TheKeysAppsHold::class);
            Router::native(AStacksScreen::Clients->value, WhichAppToWatchOn::class);
            Router::native(AStacksScreen::Pair->value, PairingAPhone::class);
            Router::native(AStacksScreen::FrontDoor->value, WhereTheHouseholdComesIn::class);
            Router::native(AStacksScreen::Invite->value, AskingSomebodyIn::class);
            Router::native(AStacksScreen::InviteNamed->value, AskingSomebodyIn::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);

            Router::native(AStacksScreen::TakeOut->value, TakingSomebodyOut::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);

            Router::native(AStacksScreen::Device->value, ConnectingADeviceForThem::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);

            Router::native(AStacksScreen::AlreadyHere->value, WhatIsAlreadyOnThisMachine::class);

            Router::native(AStacksScreen::Quality->value, ChoosingHowGood::class);

            Router::native(AStacksScreen::Wiring->value, HowTheServicesAreWired::class);

            Router::native(AStacksScreen::Plugins->value, WhatExtendsThisStack::class);
            Router::native(AStacksScreen::PluginSettings->value, HowThisPluginIsSet::class);

            Router::native(AStacksScreen::Uninstall->value, TakingItOffThisMachine::class);

            Router::native(AStacksScreen::Reset->value, PuttingTheConfigurationBack::class);

            Router::native(AStacksScreen::Words->value, WhatTheWordsMean::class);
            // A word can hold a slash, which the builder encodes and the
            // router decodes before matching, so the segment takes the rest.
            Router::native(AStacksScreen::WordAbout->value, WhatTheWordsMean::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);

            Router::native(AStacksScreen::Trace->value, WhereThisGotTo::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);
            Router::native(AStacksScreen::Accept->value, WhatAcceptingItCosts::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);
            Router::native(AStacksScreen::Run->value, WhatRunningACheckDisturbs::class)->where(AStacksScreen::aboutIsCalled(), self::ITS_NAME_MAY_HOLD_A_SLASH);
            Router::native(AStacksScreen::RunChecks->value, WhatRunningChecksDisturbs::class);

            Router::native(AStacksScreen::Walkthrough->value, WatchingOneArrive::class);

            Router::native(AStacksScreen::Health->value, HowThisStackIs::class);
        });
    }
}
