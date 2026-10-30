<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\ViewModels\ALineToSay;
use Modules\Operator\Internal\ViewModels\ASecretAsShown;
use Modules\Operator\Internal\ViewModels\AServiceReadingItAsShown;
use Modules\Operator\Internal\ViewModels\AStackSettingAsShown;

/**
 * The settings and secrets of the stack's stores, row by row, as every screen that lists them draws them.
 *
 * Whose each one is is said, except where it is the owner the screen already
 * names: a plugin's own on that plugin, or the owner a group is headed with.
 */
final readonly class HowASettingIsShown
{
    /** The catalogue key for a setting or a secret that is the core's own. */
    private const string THE_CORES = 'plugins.settings.the_cores';

    /** The catalogue key for a setting or a secret that belongs to another namespace. */
    private const string A_NAMESPACES = 'plugins.settings.a_namespaces';

    /** The catalogue key for a secret that holds a value. */
    private const string HELD = 'plugins.settings.secret_held';

    /** The catalogue key for a secret with no value yet. */
    private const string UNSET = 'plugins.settings.secret_unset';

    /** @param WhoseASettingIs $unsaid the owner the screen already names, whose rows say nothing of it */
    public function __construct(private WhoseASettingIs $unsaid) {}

    /** Whose one is, as a line, whether or not the screen names that owner. */
    public static function whose(WhoseASettingIs $whose): ALineToSay
    {
        return $whose->either(
            core: static fn(): ALineToSay => new ALineToSay(self::THE_CORES, []),
            namespace: static fn(string $namespace): ALineToSay => new ALineToSay(self::A_NAMESPACES, ['namespace' => $namespace]),
        );
    }

    /**
     * Each setting, in the order given.
     *
     * @return list<AStackSettingAsShown>
     */
    public function settings(TheSettingsRead $settings): array
    {
        $shown = [];

        foreach ($settings as $setting) {
            $shown[] = $this->setting($setting);
        }

        return $shown;
    }

    /**
     * Each secret, in the order given, never with its value.
     *
     * @return list<ASecretAsShown>
     */
    public function secrets(TheSecretsRead $secrets): array
    {
        $shown = [];

        foreach ($secrets as $secret) {
            $shown[] = $this->secret($secret);
        }

        return $shown;
    }

    private function setting(ASetting $setting): AStackSettingAsShown
    {
        return new AStackSettingAsShown(
            name: $setting->name(),
            whose: $this->unlessUnsaid($setting->whose()),
            isSet: $setting->value()->isHeld(),
            value: $setting->value()->said(),
            readers: $this->readers($setting->readers()),
        );
    }

    private function secret(ASecret $secret): ASecretAsShown
    {
        return new ASecretAsShown(
            name: $secret->name(),
            whose: $this->unlessUnsaid($secret->whose()),
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

    private function unlessUnsaid(WhoseASettingIs $whose): ?ALineToSay
    {
        $said = self::whose($whose);
        $named = self::whose($this->unsaid);

        return $said->key === $named->key && $said->filling === $named->filling ? null : $said;
    }
}
