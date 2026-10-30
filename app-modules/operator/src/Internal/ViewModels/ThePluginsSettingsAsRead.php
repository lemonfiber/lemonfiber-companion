<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** An installed plugin's settings, secrets and grants, as its services read them. */
final readonly class ThePluginsSettingsAsRead
{
    /**
     * @param string                       $plugin   what the plugin calls itself
     * @param list<APluginsSettingAsShown> $settings every setting its services read
     * @param list<ASecretAsShown>         $secrets  every secret its services read
     * @param list<AGrantAsShown>          $grants   every credential of another service granted to it
     */
    public function __construct(
        public string $plugin,
        public array $settings,
        public array $secrets,
        public array $grants,
    ) {}
}
