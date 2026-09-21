<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Setting;
use Modules\Operator\Internal\ViewModels\WhatOneSettingSays;

/**
 * One setting, turned into the row a template draws.
 *
 * The fold is the only way into {@see \Modules\Kernel\Api\WhatASettingHolds},
 * so this is the single place in the app that decides what a withheld value
 * looks like — and it decides it by carrying the stack's note through
 * unchanged.
 */
final readonly class HowASettingReads
{
    public function in(Setting $setting): WhatOneSettingSays
    {
        return $setting->holds->either(
            shown: static fn(string $value): WhatOneSettingSays => new WhatOneSettingSays(
                key: $setting->key,
                said: $value,
                withheld: false,
            ),
            withheld: static fn(string $note): WhatOneSettingSays => new WhatOneSettingSays(
                key: $setting->key,
                said: $note,
                withheld: true,
            ),
        );
    }
}
