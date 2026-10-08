<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One bundled setting a plugin declares it will change, and why. */
final readonly class ASettingItOverrides
{
    private function __construct(private string $setting, private string $why) {}

    /** The override; a blank setting is refused. */
    public static function of(string $setting, string $why): self
    {
        if (trim($setting) === '') {
            throw PluginSaysNothing::about('setting');
        }

        return new self($setting, trim($why));
    }

    /** The setting. */
    public function setting(): string
    {
        return $this->setting;
    }

    /** Why it changes it, or empty. */
    public function why(): string
    {
        return $this->why;
    }
}
