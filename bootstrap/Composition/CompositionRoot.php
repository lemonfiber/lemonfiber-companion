<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use Bootstrap\Composition\NativePHP\BehindTheLock;
use Bootstrap\Composition\NativePHP\OutOfTheOperatorsScreens;
use Bootstrap\Composition\NativePHP\ScreenRoutes;
use Bootstrap\Composition\NativePHP\TheHarnessInstead;
use Bootstrap\Composition\NativePHP\TheLockIsOnTheGlass;
use Bootstrap\Composition\NativePHP\TheOperatorIsHere;
use Bootstrap\Composition\NativePHP\TheRunloop;
use Bootstrap\Composition\NativePHP\TheTheme;
use Bootstrap\Composition\NativePHP\WhenTheLockMoves;
use Bootstrap\Composition\NativePHP\WhenThePlayerMoves;

use function config;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Lemonfiber\Native\AppsSettings as TheAppsSettingsPage;
use Lemonfiber\Native\Clock as ThePhonesClock;
use Lemonfiber\Native\Events\TheLockMoved;
use Lemonfiber\Native\Events\ThePlayerMoved;
use Lemonfiber\Native\Handover as TheSheet;
use Lemonfiber\Native\Link as TheLink;
use Lemonfiber\Native\LocalNetwork as TheLocalNetworkProbe;
use Lemonfiber\Native\Player\Player as ThePlayer;
use Lemonfiber\Native\Scanning as TheCamera;
use Lemonfiber\Native\Screen;
use Lemonfiber\Native\Storage as PlatformStore;
use Modules\Connection\Api\KeepingReadingsFor;
use Modules\Connection\Api\TheLock;
use Modules\Connection\Api\ThisDeviceKept;
use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\Store\SettingsInTheDatabase;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Modules\Device\Api\PlatformAppsSettings;
use Modules\Device\Api\PlatformAuth;
use Modules\Device\Api\PlatformLocalNetwork;
use Modules\Device\Api\PlatformNetwork;
use Modules\Device\Api\PlatformNotifier;
use Modules\Device\Api\PlatformPlayer;
use Modules\Device\Api\PlatformScanner;
use Modules\Device\Api\PlatformScreen;
use Modules\Device\Api\PlatformShare;
use Modules\Device\Api\PlatformZone;
use Modules\Device\Api\SystemClock;
use Modules\Device\Api\SystemEntropy;
use Modules\Device\Internal\Words;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\HoldsTheSealKeys;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\KeepsReadingsFor;
use Modules\Kernel\Api\KnowingThisDevice;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Networking;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\RemovalsUnderWay;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheLocalNetwork;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Kernel\Api\WorkLeftRunning;
use Modules\News\Api\Noticing;
use Modules\News\Internal\NewsKept;
use Modules\News\Internal\Store\NewsInTheDatabase;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Operator\Api\NotingWhereTheOperatorIs;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Modules\Requests\Internal\RequestsKept;
use Modules\Requests\Internal\Store\RequestsInTheDatabase;
use Modules\Sdk\Api\Assessors;
use Modules\Seal\Api\EncrypterSeal;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Services\Internal\ListingsKept;
use Modules\Services\Internal\Store\ListingsInTheDatabase;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Modules\Updates\Internal\UpkeepReadingsKept;
use Modules\Vault\Api\PlatformGrants;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformSealKeys;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWhereTheOperatorWas;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Native\Mobile\Edge\TreeObservers;

/**
 * The composition root.
 *
 * Named for what it is rather than for the framework slot it fills, and living
 * beside `bootstrap/providers.php` — the file that names it — rather than under
 * an `app/` directory that held nothing else. Laravel does not require the `App`
 * namespace anywhere; `providers.php` returns a class list, and nothing in this
 * application has models to discover.
 *
 * This directory is the one place in the application permitted to name both a
 * port and the adapter that implements it: this provider binds the platform and
 * what the phone keeps, and {@see TheWayToEveryStack} the way to every stack.
 * Every other class receives what it needs through its constructor and never
 * learns which implementation it got — which is what makes a capability module
 * testable without a device, a network or a stack to talk to.
 */

final class CompositionRoot extends ServiceProvider
{
    /** Where the platform reads what to install this application as. */
    private const string WHAT_THE_PLATFORM_INSTALLS_US_AS = 'nativephp.app_id';

    /** The setting that pins how iOS draws its own sheets, alerts and keyboard. */
    private const string HOW_THE_PLATFORM_DRAWS_ITS_OWN = 'nativephp.appearance';

    /** The setting that picks the colour of Android's status bar and navigation bar icons. */
    private const string THE_ICONS_ON_THE_SYSTEM_BARS = 'nativephp.android.status_bar_style';

    /** The tag every store of what the phone keeps is registered under. */
    private const string WHAT_THE_PHONE_KEEPS = 'what-the-phone-keeps';

    /** The tag every keeper of one stack is registered under. */
    private const string WHAT_IS_KEPT_OF_A_STACK = 'what-is-kept-of-a-stack';

    /** The tag every store of readings is registered under. */
    private const string EVERY_STORE_OF_READINGS = 'every-store-of-readings';

    /**
     * Which theme is on the glass, one for the life of the process, made when first asked for.
     *
     * Held here rather than bound as itself, because the composition root is
     * all that paints it: the boot paints the member's theme and the
     * navigation stack each screen's. What the design module reads is the port
     * it implements, bound to this one instance.
     */
    private ?TheTheme $theme = null;

    public function register(): void
    {
        // The identity, before anything is wired. `config/nativephp.php`
        // is written by `native:install` and is not in this repository, so what
        // it says about the identity is whatever the environment of whoever ran
        // that command said — which is the one thing the requirement names. The
        // declared identity is applied over it here, and a build configured as
        // another application is refused rather than quietly overridden.
        //
        // Not work: no read of the environment, no file, no socket. It is one
        // value replaced with the one this repository declares, and the build
        // commands that assemble a bundle read the config after this has run.
        config()->set(
            self::WHAT_THE_PLATFORM_INSTALLS_US_AS,
            WhatThisBuildInstallsAs::orRefuse(config(self::WHAT_THE_PLATFORM_INSTALLS_US_AS)),
        );

        // Both themes are dark whatever the phone is set to, so what the
        // platform draws itself is dark as well: iOS draws its sheets, alerts
        // and keyboard dark, and Android draws light icons on its system bars.
        // Applied over `config/nativephp.php` for the reason the identity is,
        // and read by the build commands after this has run.
        config()->set(self::HOW_THE_PLATFORM_DRAWS_ITS_OWN, 'dark');
        config()->set(self::THE_ICONS_ON_THE_SYSTEM_BARS, 'light');

        $this->bindThePlatform();
        $this->bindWhatThePhoneKeeps();
    }

    public function boot(): void
    {
        // The member's theme, which every screen opens in until one is drawn
        // for an operator, painted after `nativephp/mobile-ui` has set its own
        // resolver. {@see TheTheme} carries the reasoning.
        //
        // `$this->app->booted()` rather than `$this->booted()`, which fires at
        // the end of *this* provider's boot rather than everybody's, and differs
        // only in the case that matters.
        $this->app->booted($this->paintTheMembersTheme(...));

        // `Route::native()`, replaced so that a screen is built through the
        // container rather than with `new`. NativePHP's own router cannot give
        // a screen a port, and a screen that reached the container itself would
        // be service location — so the substitution happens here, where naming
        // the container is what this directory is for.
        //
        // In `boot()` and after the macro it replaces, which the NativePHP
        // package adds in its own boot. Provider order between two discovered
        // packages is not something either of them decides, so this is asserted
        // by `ScreenRoutesReplaceTheVendorsTest` rather than assumed.
        //
        // Handed *how to build a screen* rather than the container. A router
        // holding a container could resolve anything, and the rule against a
        // container parameter exists because that is what a class holding one
        // eventually does.
        // Which runloop, decided here because *whether there is a device* is a
        // fact about this composition rather than about routing. A suite must
        // never enter the real one: it blocks against the bridge, so a test
        // that reached it would hang rather than fail.
        $runloop = $this->app->runningUnitTests() ? new TheHarnessInstead() : new TheRunloop($this->theTheme()->forTheScreen(...));

        new ScreenRoutes($this->screen(...), $runloop)->declare();

        // The lock, kept over whatever screen is on view. The device wakes the
        // app with an event that carries nothing when its lock stands again or
        // opens, and {@see WhenTheLockMoves} reads the lock afresh; the device
        // keeps its window covered until {@see TheLockIsOnTheGlass} says the
        // lock screen has been published. After boot, where the dispatcher is
        // the one every provider has registered with.
        $this->app->booted($this->keepTheLock(...));

        // Where the operator is, noted as each screen comes to the front, so the
        // app opens there after the lock and a chosen stack opens on its tab.
        $this->app->booted($this->noteWhereTheOperatorIs(...));
    }

    /** The phone itself: its clock, its randomness, its secure store and the platform's own answers. */
    private function bindThePlatform(): void
    {
        // The one line that says which clock the application runs on, and the
        // only place in the codebase allowed to say it. Everything else takes a
        // `Clock` and never learns it got the platform's rather than a frozen
        // one — which is what makes a test about an expiring session a
        // statement rather than a wait.
        //
        // Bound as a singleton because reading the time is stateless and a
        // second instance would answer identically; one object is the honest
        // description of that.
        $this->app->singleton(Clock::class, static fn(): Clock => new SystemClock());

        // The other hidden input, bound the same way and with the same
        // consequence: nothing that needs a value nobody can guess learns
        // whether it got the platform's randomness or a counter, which is what
        // makes a test about a retry a statement rather than a guess.
        $this->app->singleton(Entropy::class, static fn(): Entropy => new SystemEntropy());

        // Not a singleton. The platform's store is a handle to something
        // outside this process, and holding one for the life of a long-running
        // app is how a keychain that was unlocked at launch goes on reading as
        // unlocked after the device has locked.
        $this->app->bind(
            SecureStorage::class,
            static fn(): SecureStorage => new PlatformKeychain(new PlatformStore()),
        );

        // Handing a diagnostic report to the operator, which is the only way
        // one leaves this device. The app assembles and does not transmit, and
        // both halves are structural: `Diagnostics` holds nothing
        // that could send, and `Sharing` takes nowhere to send to.
        //
        // Not a singleton, for `SecureStorage`'s reason: the share sheet is a
        // handle to something outside this process, and one held for the life
        // of a long-running app is a handle to a platform state that has since
        // moved on.
        $this->app->bind(Sharing::class, static fn(): Sharing => new PlatformShare(new TheSheet()));

        // The device's own player, handed only what the core stated. Bound
        // rather than a singleton, for the share sheet's reason: the player is
        // a handle to something outside this process.
        $this->app->bind(Playing::class, static fn(): Playing => new PlatformPlayer(new ThePlayer()));

        // Whether this device is on a network at all, which is the one question
        // about reaching a stack that can be answered without sending anything.
        // Bound rather than a singleton for the reason the store above is: the
        // facade is a handle to something outside this process, and a phone
        // changes network while the app is open.
        $this->app->bind(
            Networking::class,
            static fn(): Networking => new PlatformNetwork(new TheLink()),
        );

        $this->app->bind(
            TheLocalNetwork::class,
            static fn(): TheLocalNetwork => new PlatformLocalNetwork(new TheLocalNetworkProbe()),
        );

        $this->app->bind(
            TheAppsSettings::class,
            static fn(): TheAppsSettings => new PlatformAppsSettings(new TheAppsSettingsPage()),
        );

        // Which zone the phone's clock is set to, asked each time rather than
        // held: a phone can cross a border between two screens.
        $this->app->bind(
            LocalZone::class,
            static fn(): LocalZone => new PlatformZone(new ThePhonesClock()),
        );

        // Bound, not a singleton, for the same reason the store above is not:
        // the bridge's centre is a handle to something outside this process,
        // and this runtime is persistent.
        //
        // Local rather than pushed, which is the decision this line records. A
        // push payload reaches the handset through Google's or Apple's relay —
        // a third party reading what a stack said about somebody's home — and
        // NothingLeavesThisDeviceTest is the rule that refuses it. A local
        // notification is composed and shown on the device and never leaves it.
        $this->app->bind(
            Notifier::class,
            // Named by class rather than built by a closure here. Both of this
            // adapter's dependencies are concrete classes with no alternative —
            // lemonfiber's own notification centre and the catalogue reader —
            // so there is no implementation choice for a closure to state. The
            // decision this line makes is the one that matters and is still
            // written down: `Notifier` is `PlatformNotifier`.
            PlatformNotifier::class,
        );

        // Bound for the same reason: a window is outside this process, and an
        // app holding one from launch would go on answering with the state it
        // saw then.
        //
        // Protecting a backgrounded window is not reached through this binding
        // at all. The native half
        // installs a lifecycle observer as the app starts and protects a
        // backgrounded app whether or not anything here is ever resolved — a
        // requirement with no exceptions should not depend on a container entry.
        $this->app->bind(
            Capture::class,
            static fn(): Capture => new PlatformScreen(new Screen()),
        );

        // The camera, reading a pairing code.
        //
        // Handed this application's own camera rather than the plugin's. The
        // difference that matters is not ownership: it is that our call blocks
        // while the scanner is on screen and answers with what was read, so the
        // code never goes near an event — which on Android is injected into the
        // WebView as a DOM event, a Livewire dispatch and an HTTP POST. Pairing
        // material is the one payload where that is a disclosure.
        $this->app->bind(
            Scanning::class,
            // Built in a named method rather than here, for the reason
            // `screen()` gives.
            $this->theScanner(...),
        );

        // The same handle again, and bound for the same reason. The app locks on
        // backgrounding and asks again after a period the operator sets — both of which are decisions about *when*, made in
        // `LockRule` on the native side where they can be tested without a
        // handset. This is only how an answer becomes a Lock.
        $this->app->bind(
            DeviceAuth::class,
            // By class, for the reason given above the `Notifier` binding.
            PlatformAuth::class,
        );

        // Which theme is on the glass, for an element handed a colour as a
        // value. The one instance the composition root paints, for the reason
        // `WhatEachStackLastNamed` is a singleton: it holds what one screen
        // painted and the next one reads, across the one process this runtime
        // keeps. It reaches the keychain afresh for each screen, for the
        // keychain's reason above.
        $this->app->singleton(WhichThemeIsOnTheGlass::class, $this->theTheme(...));
    }

    /** What the phone keeps between launches, the seal over it, and every set of keepers asked as one. */
    private function bindWhatThePhoneKeeps(): void
    {
        // The paired machines, in the same store and bound for the same reason.
        // A separate port from the one above rather than a second method on it,
        // because the two have opposite obligations: a session is what this app
        // may hold and must not spread, and a stack is what it must retain and
        // no discard may take with it. One port for both would be the place where
        // the first piece of code to write one out takes the other with it.
        $this->app->bind(
            Stacks::class,
            static fn(): Stacks => new PlatformStacks(new PlatformStore()),
        );

        // The word each stack's one line last said, in the same store and bound
        // the same way. A third port rather than a method on either of the two
        // above, because what it holds has obligations neither of theirs does:
        // it is the only retained value this app *shows*, so carrying its age
        // applies to it and to nothing else here — which is why it answers `Showing` and
        // they answer collections. Folding it into `Stacks` would put a value
        // that must carry its age behind a port whose other answers must not.
        $this->app->bind(
            Standings::class,
            static fn(): Standings => new PlatformStandings(new PlatformStore()),
        );

        // The handle of work left running on each stack, in the same store and
        // bound the same way. A fourth port rather than a method on any of the
        // three above, because it answers a different question: not who this
        // device signs in as, what it is paired with or what a stack's one line
        // last said, but which work a screen left running there.
        $this->app->bind(
            WorkLeftRunning::class,
            static fn(): WorkLeftRunning => new PlatformWorkLeftRunning(new PlatformStore(), new PlatformStacks(new PlatformStore())),
        );

        // The grant this device plays a member's titles with on each stack, in
        // the same store and bound the same way. A keeper of the stack, so
        // Remove from phone lets it go.
        $this->app->bind(
            KeepingTheGrant::class,
            static fn(): KeepingTheGrant => new PlatformGrants(new PlatformStore()),
        );

        // Where the operator was: the stack last on view and the tab last used
        // on each, in the same store and bound the same way. A marker, so it
        // goes with Clear saved data and with Remove from phone.
        $this->app->bind(
            WhereTheOperatorWas::class,
            static fn(): WhereTheOperatorWas => new PlatformWhereTheOperatorWas(new PlatformStore()),
        );

        // The two keys that seal what the phone keeps, in the same store and
        // bound the same way. Beside the session rather than beside the data
        // they seal: a key kept in the application's own files would sit next
        // to what it locks, and this store is the one place on the phone that
        // is not those files.
        $this->app->bind(
            HoldsTheSealKeys::class,
            static fn(): HoldsTheSealKeys => new PlatformSealKeys(new PlatformStore()),
        );

        // Sealing itself, named by class. Its two dependencies are ports bound
        // here, the keys above and the entropy at the top, so the container
        // builds it from those and the one decision this line makes is which
        // seal: Laravel's encrypter under the phone's own key, never under the
        // framework's.
        $this->app->bind(Sealed::class, EncrypterSeal::class);

        // The newest health reading of each stack, in the app's own database.
        // Both are `health`'s own, the port its decisions ask and the store
        // walled inside it, and this is the one place outside that store to
        // name it.
        // Named by class: its one dependency is the database connection the
        // framework already binds, so the decision this line makes is which
        // store. What reaches it is sealed first, by `health`, so the database
        // file beside the framework's own key holds nothing that key could
        // open.
        $this->app->bind(HealthReadingsKept::class, HealthReadingsInTheDatabase::class);

        // The newest reading of where each stack stands on being up to date,
        // `updates`'s own port and the store walled inside it, bound as the
        // health readings are and for their reasons.
        $this->app->bind(UpkeepReadingsKept::class, UpkeepReadingsInTheDatabase::class);

        // The newest listing of what each stack runs, `services`'s own port
        // and the store walled inside it, bound as the health readings are.
        $this->app->bind(ListingsKept::class, ListingsInTheDatabase::class);

        // The newest reading of what each stack's household asked for,
        // `requests`'s own port and the store walled inside it, bound as the
        // health readings are.
        $this->app->bind(RequestsKept::class, RequestsInTheDatabase::class);

        // `connection`'s settings — how long the app may be away before the
        // lock asks again — sealed by `connection` before they reach it.
        $this->app->bind(SettingsKept::class, SettingsInTheDatabase::class);

        // The id this install plays under, kept sealed with those settings.
        $this->app->bind(KnowingThisDevice::class, ThisDeviceKept::class);

        // What `news` keeps of each stack: the kinds marked as new and the
        // newest of each seen, sealed by `news` before they reach it.
        $this->app->bind(NewsKept::class, NewsInTheDatabase::class);

        // What each stack last named as newest, held in memory for the life of
        // the process and never kept. A singleton, and it has to be: it is
        // what one screen hears and the next screen draws, and the runtime
        // keeps one process, and so one container, across every screen.
        $this->app->singleton(WhatEachStackLastNamed::class);

        // What is on the player, held in memory for the life of the process
        // and never kept, and a singleton for the same reason: the player
        // plays on over whichever screen is under it, and what the page that
        // put it there pressed is what each move of it is told against.
        $this->app->singleton(WhatIsPlaying::class);

        // How long readings are kept is one of the phone's settings, kept in
        // `connection`'s row beside the lock's time away, and asked for through
        // the kernel by what lets go of readings older than it.
        $this->app->bind(KeepsReadingsFor::class, KeepingReadingsFor::class);

        // Every store of readings, registered under one tag and asked as one
        // to let go of what is older than readings are kept for. A kind of
        // reading is let go of for its age by adding its store here.
        $this->app->tag([HealthReadingsKept::class, UpkeepReadingsKept::class, ListingsKept::class, RequestsKept::class], self::EVERY_STORE_OF_READINGS);
        $this->app->when(EveryStoreOfReadings::class)
            ->needs(ForgetsOldReadings::class)
            ->giveTagged(self::EVERY_STORE_OF_READINGS);
        $this->app->bind(ForgetsOldReadings::class, EveryStoreOfReadings::class);

        // Every store of what the phone keeps, registered under one tag and
        // cleared together where the seal's key had to be made afresh: what
        // was sealed under the old key cannot be opened under the new one. A
        // store is added to what is cleared by adding it here, and nothing
        // that clears has to know how many there are.
        $this->app->tag(
            [HealthReadingsKept::class, UpkeepReadingsKept::class, ListingsKept::class, RequestsKept::class, SettingsKept::class, Noticing::class, Standings::class, WorkLeftRunning::class, WhereTheOperatorWas::class],
            self::WHAT_THE_PHONE_KEEPS,
        );
        $this->app->when(EveryStoreThePhoneKeeps::class)
            ->needs(ForgetsEverythingKept::class)
            ->giveTagged(self::WHAT_THE_PHONE_KEEPS);
        $this->app->bind(ForgetsEverythingKept::class, EveryStoreThePhoneKeeps::class);

        // Every keeper of one stack, asked as one when the stack is removed
        // from the phone: the pairing first, so the stack leaves every list at
        // once, then the session, the readings and the markers. What was begun
        // is recorded in the same secure store as the pairing it removes.
        $this->app->tag(
            [Stacks::class, SecureStorage::class, KeepingTheGrant::class, KeepingTheLastReading::class, KeepingTheLastUpkeep::class, KeepingWhatItRuns::class, KeepingWhatWasAsked::class, Noticing::class, Standings::class, WorkLeftRunning::class, WhereTheOperatorWas::class, Assessors::class],
            self::WHAT_IS_KEPT_OF_A_STACK,
        );
        $this->app->when(EveryKeeperOfAStack::class)
            ->needs(ForgetsAStack::class)
            ->giveTagged(self::WHAT_IS_KEPT_OF_A_STACK);
        $this->app->bind(ForgetsAStack::class, EveryKeeperOfAStack::class);
        $this->app->bind(
            RemovalsUnderWay::class,
            static fn(): RemovalsUnderWay => new PlatformStacks(new PlatformStore()),
        );
    }

    /**
     * Note the stack and tab of each screen that comes to the front.
     *
     * A method for the reason {@see keepTheLock()} is one: `make()` raises a
     * checked exception.
     */
    private function noteWhereTheOperatorIs(): void
    {
        TreeObservers::register(new TheOperatorIsHere($this->app->make(NotingWhereTheOperatorIs::class)));
    }

    /** The member's theme, painted once every provider has booted. */
    private function paintTheMembersTheme(): void
    {
        $this->theTheme()->paint(WhoseTheme::Member);
    }

    /** The one theme on the glass, made the first time anything asks for it. */
    private function theTheme(): TheTheme
    {
        return $this->theme ??= new TheTheme($this->theKeychain(...));
    }

    /** The keychain, made afresh each time it is asked for, as its binding says. */
    private function theKeychain(): SecureStorage
    {
        return $this->app->make(SecureStorage::class);
    }

    /**
     * Hear the device's lock move, and tell it when the lock screen is drawn;
     * and hear the player move, wherever it plays.
     *
     * A method rather than a closure, because `make()` raises a checked
     * exception and a closure's caller cannot see what it throws.
     */
    private function keepTheLock(): void
    {
        TreeObservers::register(new TheLockIsOnTheGlass());
        $this->app->make(Dispatcher::class)->listen(TheLockMoved::class, WhenTheLockMoves::class);
        $this->app->make(Dispatcher::class)->listen(ThePlayerMoved::class, WhenThePlayerMoves::class);
    }

    /**
     * The camera, with the catalogue the prompt over it is captioned from.
     *
     * A method rather than a closure, for two reasons that both point here.
     * `Container::make()` raises a checked exception, which a closure may not,
     * for the reason {@see self::screen()} gives. And a container may not be a
     * *parameter* either, so this reaches the provider's own `$this->app`
     * rather than being handed one: a class that receives a container can
     * resolve anything, which is what the rule is about, and a provider already
     * has one by being a provider.
     */
    private function theScanner(): Scanning
    {
        return new PlatformScanner($this->app->make(TheCamera::class), $this->app->make(Words::class));
    }

    /**
     * One screen, built with whatever it declared in its constructor — or the
     * lock, while the device's lock stands, which {@see BehindTheLock} decides.
     * Before the lock is asked, {@see OutOfTheOperatorsScreens} puts the
     * member's own screen in place of an operator's for a stack this phone
     * holds a member's session for, so the lock has the last word over both.
     *
     * The one place a screen meets a port. NativePHP builds a screen with `new
     * $class`, so without this a screen could hold nothing — and a screen that
     * reached the container itself would hide what it needs.
     *
     * Here rather than as a closure handed to {@see ScreenRoutes}: `make()`
     * raises where a route names a class this application does not have, and
     * raising a checked exception inside a closure is forbidden because a
     * closure's caller cannot see what it throws. A method's can.
     *
     * `mixed` rather than `NativeComponent`, so that the check about what came
     * back lives with the router that is about to call methods on it.
     *
     * @param array<mixed> $params the route's parameters
     */
    private function screen(string $class, array $params): mixed
    {
        $lock = new BehindTheLock($this->app->make(TheLock::class), $this->made(...));

        return new OutOfTheOperatorsScreens($this->theKeychain(), $params, $lock->screen(...))->screen($class);
    }

    /**
     * One class, made by the container.
     *
     * A method for the reason {@see Screen()} is one: `make()` raises a checked
     * exception, and a closure's caller cannot see what it throws.
     */
    private function made(string $class): mixed
    {
        return $this->app->make($class);
    }
}
