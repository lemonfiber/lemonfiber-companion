<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
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
use Modules\Operator\Internal\Presenters\HowAPluginsSettingsRead;
use Modules\Operator\Internal\ViewModels\WhatAPluginsSettingsShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * One plugin's settings, secrets and grants, and changing a setting: rehearsed, agreed to, and followed.
 *
 * Every setting and secret one of the plugin's services reads is listed. A
 * setting is changed or put back to its default by a rehearsal first, and the
 * yes carries the offer that rehearsal answered. A secret is listed without its
 * value and is never changed here. The stream's settings replace what was read.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowThisPluginIsSet extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use ChangesASetting;

    public const string TEMPLATE = 'operator::how-this-plugin-is-set';

    /** The plugin, once it was found among those installed. */
    public ?APlugin $plugin = null;

    /** Every setting and secret, as last read or heard. */
    public ?TheServicesSettings $read = null;

    /** The plugin's settings, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?WhatAPluginsSettingsShow $answered = null;

    public function __construct(
        protected readonly SettingWhatServicesRead $tuning,
        private readonly ExtendingTheStack $extending,
        private readonly Clock $clock,
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}


    /** Read the plugin and its settings again. */
    public function again(): void
    {
        $this->answered = null;
        $this->plugin = null;
        $this->read = null;
    }

    /** The plugin's settings, asked once per frame, after where a change has got to and what the stream said. */
    public function answer(): WhatAPluginsSettingsShow
    {
        $this->howItIsGoing();
        $this->hearTheSettings();

        return $this->answered ??= $this->asked();
    }

    /** The setting of that name one of the plugin's services reads, as last read, or none; a secret is never one. */
    protected function itsSetting(string $setting): ?ASetting
    {
        $plugin = $this->plugin;
        $read = $this->read;
        $found = null;

        foreach ($plugin instanceof APlugin && $read instanceof TheServicesSettings ? $read->readByAnyOf($plugin->services())->settings() : [] as $held) {
            $found = $held->name() === $setting ? $held : $found;
        }

        return $found;
    }

    private function asked(): WhatAPluginsSettingsShow
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAPluginsSettingsShow => $this->plugin instanceof APlugin ? $this->settingsOf($this->plugin, $stack, $session) : $this->found($stack, $session),
            notHeld: static fn(): WhatAPluginsSettingsShow => new HowAPluginsSettingsRead()->signedOut(),
        );
    }

    /** The plugin, found among those installed, with its settings; one no longer installed is said as that. */
    private function found(Stack $stack, Session $session): WhatAPluginsSettingsShow
    {
        return $this->extending->installedOn($stack, $session)->either(
            found: function (ThePlugins $plugins) use ($stack, $session): WhatAPluginsSettingsShow {
                foreach ($plugins->installed() as $plugin) {
                    $this->plugin = $plugin->id() === $this->around->aboutOn($this) ? $plugin : $this->plugin;
                }

                return $this->plugin instanceof APlugin ? $this->settingsOf($this->plugin, $stack, $session) : new HowAPluginsSettingsRead()->notInstalled();
            },
            met: $this->lettingGoIfRefused($stack, new HowAPluginsSettingsRead()->met(...)),
        );
    }

    private function settingsOf(APlugin $plugin, Stack $stack, Session $session): WhatAPluginsSettingsShow
    {
        $read = $this->read;

        if ($read instanceof TheServicesSettings) {
            return new HowAPluginsSettingsRead()->these($plugin, $read, $this->clock->now());
        }

        return $this->tuning->readOn($stack, $session)->either(
            found: function (TheServicesSettings $settings) use ($plugin): WhatAPluginsSettingsShow {
                $this->read = $settings;

                return new HowAPluginsSettingsRead()->these($plugin, $settings, $this->clock->now());
            },
            met: $this->lettingGoIfRefused($stack, new HowAPluginsSettingsRead()->met(...)),
        );
    }
}
