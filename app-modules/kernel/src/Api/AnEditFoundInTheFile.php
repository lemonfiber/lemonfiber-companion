<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A setting changed outside lemonfiber since it last wrote one: what it wrote, and what the file holds now.
 */
final readonly class AnEditFoundInTheFile
{
    private function __construct(
        public WhatASettingHolds $wrote,
        public WhatASettingHolds $found,
    ) {}

    public static function shown(string $wrote, string $found): self
    {
        return new self(WhatASettingHolds::shown($wrote), WhatASettingHolds::shown($found));
    }

    public static function withheld(string $wrote, string $found): self
    {
        return new self(WhatASettingHolds::withheld($wrote), WhatASettingHolds::withheld($found));
    }
}
