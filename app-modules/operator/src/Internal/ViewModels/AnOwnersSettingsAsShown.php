<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The settings and secrets one owner holds that no installed plugin reads, flattened for a template. */
final readonly class AnOwnersSettingsAsShown
{
    /**
     * @param ALineToSay                 $whose    whose they are, the core's or a namespace's
     * @param list<AStackSettingAsShown> $settings each of its settings
     * @param list<ASecretAsShown>       $secrets  each of its secrets, never with a value
     */
    public function __construct(
        public ALineToSay $whose,
        public array $settings,
        public array $secrets,
    ) {}
}
