<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\ViewModels\APluginsSettingAsShown;
use Modules\Operator\Internal\ViewModels\ASecretAsShown;
use Modules\Operator\Internal\ViewModels\AServiceReadingItAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAPluginsSettingsShow;
use Modules\Operator\Internal\ViewModels\WhoseItIsAsShown;

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

    /** Every setting and secret one of the plugin's services reads. */
    public function these(APlugin $plugin, TheServicesSettings $settings): WhatAPluginsSettingsShow
    {
        $its = $settings->readByAnyOf($plugin->services());

        return new WhatAPluginsSettingsShow(
            went: HowTheReadingWent::itCameBack(),
            isInstalled: true,
            plugin: $plugin->shown(),
            settings: $this->settings($plugin, $its->settings()),
            secrets: $this->secrets($plugin, $its->secrets()),
        );
    }

    /** The plugin is not among those installed. */
    public function notInstalled(): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(went: HowTheReadingWent::itCameBack(), isInstalled: false, plugin: '', settings: [], secrets: []);
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): WhatAPluginsSettingsShow
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading either. */
    public function met(Obstacle $why): WhatAPluginsSettingsShow
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    private function nothingFrom(HowTheReadingWent $went): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(went: $went, isInstalled: true, plugin: '', settings: [], secrets: []);
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
        $whose = $this->whose($plugin, $setting->whose());

        return new APluginsSettingAsShown(
            name: $setting->name(),
            whoseSaid: $whose->said,
            namespace: $whose->namespace,
            isSet: $setting->value()->isHeld(),
            value: $setting->value()->said(),
            readers: $this->readers($setting->readers()),
        );
    }

    private function secret(APlugin $plugin, ASecret $secret): ASecretAsShown
    {
        $whose = $this->whose($plugin, $secret->whose());

        return new ASecretAsShown(
            name: $secret->name(),
            whoseSaid: $whose->said,
            namespace: $whose->namespace,
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
    private function whose(APlugin $plugin, WhoseASettingIs $whose): WhoseItIsAsShown
    {
        return $whose->either(
            core: static fn(): WhoseItIsAsShown => new WhoseItIsAsShown(said: self::THE_CORES, namespace: ''),
            namespace: static fn(string $namespace): WhoseItIsAsShown => new WhoseItIsAsShown(said: $namespace === $plugin->id() ? '' : self::A_NAMESPACES, namespace: $namespace),
        );
    }
}
