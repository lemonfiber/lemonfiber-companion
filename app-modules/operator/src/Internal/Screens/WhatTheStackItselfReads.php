<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\ChangesASetting;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheStacksOwnSettingsRead;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\ViewModels\WhatTheStackItselfReadsShows;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The settings and secrets in the stack's stores that no installed plugin reads, and changing a setting.
 *
 * What is left once every installed plugin's services are set aside is the
 * stack's own: the core's, and a namespace's that no plugin brought. It is
 * grouped by whose it is, and changed exactly as a plugin's setting is, through
 * {@see ChangesASetting}. A secret is listed without its value and is never
 * changed here. The stream's settings replace what was read.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatTheStackItselfReads extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ChangesASetting;
    use ReadsAStackOnceAFrame;

    /** Every service of every installed plugin, once they were read. */
    public ?Services $thePluginsServices = null;

    /** Every setting and secret, as last read or heard. */
    public ?TheServicesSettings $read = null;

    /** The stack's own settings, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatTheStackItselfReadsShows $answered = null;

    public function __construct(
        protected readonly SettingWhatServicesRead $tuning,
        private readonly ExtendingTheStack $extending,
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Read the plugins and the settings again. */
    public function again(): void
    {
        $this->answered = null;
        $this->thePluginsServices = null;
        $this->read = null;
    }

    /** The stack's own settings, asked once per frame after where a change has got to and what the stream said; the settings follow the plugins by a frame. */
    public function answer(): WhatTheStackItselfReadsShows
    {
        $this->howItIsGoing();
        $this->hearTheSettings();
        $answer = $this->answered ?? $this->asked();
        $this->answered = $answer->waits ? null : $answer;

        return $answer;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-the-stack-itself-reads');
    }

    /** The setting of that name no installed plugin reads, as last read, or none; a secret is never one. */
    private function itsSetting(string $setting): ?ASetting
    {
        $services = $this->thePluginsServices;
        $read = $this->read;
        $found = null;

        foreach ($services instanceof Services && $read instanceof TheServicesSettings ? $read->readByNoneOf($services)->settings() : [] as $held) {
            $found = $held->name() === $setting ? $held : $found;
        }

        return $found;
    }

    private function asked(): WhatTheStackItselfReadsShows
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackItselfReadsShows => $this->thePluginsServices instanceof Services ? $this->settingsBeside($this->thePluginsServices, $stack, $session) : $this->found($stack, $session),
            notHeld: static fn(): WhatTheStackItselfReadsShows => new HowTheStacksOwnSettingsRead()->signedOut(),
        );
    }

    /** Every installed plugin's services, read first, so what they read can be set aside. */
    private function found(Stack $stack, Session $session): WhatTheStackItselfReadsShows
    {
        if (! $this->mayReadItsStack()) {
            return new HowTheStacksOwnSettingsRead()->waits();
        }

        return $this->extending->installedOn($stack, $session)->either(
            found: function (ThePlugins $plugins) use ($stack, $session): WhatTheStackItselfReadsShows {
                $this->thePluginsServices = $plugins->installed()->services();

                return $this->settingsBeside($this->thePluginsServices, $stack, $session);
            },
            met: $this->lettingGoIfRefused($stack, new HowTheStacksOwnSettingsRead()->met(...)),
        );
    }

    private function settingsBeside(Services $thePluginsServices, Stack $stack, Session $session): WhatTheStackItselfReadsShows
    {
        $read = $this->read;

        if ($read instanceof TheServicesSettings) {
            return new HowTheStacksOwnSettingsRead()->these($read, $thePluginsServices);
        }

        if (! $this->mayReadItsStack()) {
            return new HowTheStacksOwnSettingsRead()->waits();
        }

        return $this->tuning->readOn($stack, $session)->either(
            found: function (TheServicesSettings $settings) use ($thePluginsServices): WhatTheStackItselfReadsShows {
                $this->read = $settings;

                return new HowTheStacksOwnSettingsRead()->these($settings, $thePluginsServices);
            },
            met: $this->lettingGoIfRefused($stack, new HowTheStacksOwnSettingsRead()->met(...)),
        );
    }
}
