<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ALineToSay;
use Modules\Operator\Internal\ViewModels\APluginsSettingAsShown;
use Modules\Operator\Internal\ViewModels\ASecretAsShown;
use Modules\Operator\Internal\ViewModels\AServiceReadingItAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThePluginsSettingsAsRead;
use Modules\Operator\Internal\ViewModels\WhatAPluginsSettingsShow;

/**
 * One plugin's settings and secrets, as the template draws them.
 */
final readonly class HowAPluginsSettingsRead
{
    /** The catalogue key for a setting or a secret that is the core's own. */
    private const string THE_CORES = 'plugins.settings.the_cores';

    /** The catalogue key for a setting or a secret that belongs to another namespace. */
    private const string A_NAMESPACES = 'plugins.settings.a_namespaces';

    /** The catalogue key for a secret that holds a value. */
    private const string HELD = 'plugins.settings.secret_held';

    /** The catalogue key for a secret with no value yet. */
    private const string UNSET = 'plugins.settings.secret_unset';

    /** Every setting and secret one of the plugin's services reads, and every grant it holds, dated against the frame's moment. */
    public function these(APlugin $plugin, TheServicesSettings $settings, Instant $now): WhatAPluginsSettingsShow
    {
        $its = $settings->readByAnyOf($plugin->services());
        $grants = [];

        foreach ($plugin->grants() as $which => $grant) {
            $grants[] = new HowAGrantReads()->one($grant, $which, $now);
        }

        return new WhatAPluginsSettingsShow(
            HowTheReadingWent::itCameBack(),
            new ThePluginsSettingsAsRead($plugin->shown(), $this->settings($plugin, $its->settings()), $this->secrets($plugin, $its->secrets()), $grants),
        );
    }

    /** The plugin is not among those installed. */
    public function notInstalled(): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::itCameBack());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading either. */
    public function met(Obstacle $why): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::somethingStopped($why));
    }

    /**
     * Each setting, in name order.
     *
     * @return list<APluginsSettingAsShown>
     */
    private function settings(APlugin $plugin, TheSettingsRead $settings): array
    {
        $shown = [];

        foreach ($settings as $setting) {
            $shown[] = $this->setting($plugin, $setting);
        }

        return $shown;
    }

    /**
     * Each secret, in name order.
     *
     * @return list<ASecretAsShown>
     */
    private function secrets(APlugin $plugin, TheSecretsRead $secrets): array
    {
        $shown = [];

        foreach ($secrets as $secret) {
            $shown[] = $this->secret($plugin, $secret);
        }

        return $shown;
    }

    private function setting(APlugin $plugin, ASetting $setting): APluginsSettingAsShown
    {
        return new APluginsSettingAsShown(
            name: $setting->name(),
            whose: $this->whose($plugin, $setting->whose()),
            isSet: $setting->value()->isHeld(),
            value: $setting->value()->said(),
            readers: $this->readers($setting->readers()),
        );
    }

    private function secret(APlugin $plugin, ASecret $secret): ASecretAsShown
    {
        return new ASecretAsShown(
            name: $secret->name(),
            whose: $this->whose($plugin, $secret->whose()),
            heldSaid: $secret->isHeld() ? self::HELD : self::UNSET,
            fromSaid: $secret->from()->saidOnTheScreen(),
            readers: $this->readers($secret->readers()),
        );
    }

    /**
     * Each service reading it.
     *
     * @return list<AServiceReadingItAsShown>
     */
    private function readers(TheServicesReadingIt $readers): array
    {
        $shown = [];

        foreach ($readers as $reader) {
            $shown[] = $this->reader($reader);
        }

        return $shown;
    }

    private function reader(AServiceReadingIt $reader): AServiceReadingItAsShown
    {
        return new AServiceReadingItAsShown(
            service: $reader->service()->named(),
            variable: $reader->variable(),
            declaresADefault: $reader->default()->isHeld(),
            default: $reader->default()->said(),
        );
    }

    /** Whose it is, said only where it is not the plugin's own. */
    private function whose(APlugin $plugin, WhoseASettingIs $whose): ?ALineToSay
    {
        $said = $whose->either(
            core: static fn(): ALineToSay => new ALineToSay(self::THE_CORES, []),
            namespace: static fn(string $namespace): ALineToSay|AsText => $namespace === $plugin->id() ? AsText::nothing() : new ALineToSay(self::A_NAMESPACES, ['namespace' => $namespace]),
        );

        return $said instanceof ALineToSay ? $said : null;
    }
}
