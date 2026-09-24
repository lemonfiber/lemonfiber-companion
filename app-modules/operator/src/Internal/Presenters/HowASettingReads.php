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
 *
 * **Every row gets its origin**, folded by {@see HowAnOriginReads}, which
 * every screen that attributes shares. It is required wherever a setting is
 * shown, so all four arms produce a key and the template has no branch to get
 * wrong.
 */
final readonly class HowASettingReads
{
    public function in(Setting $setting): WhatOneSettingSays
    {
        $from = new HowAnOriginReads()->of($setting->from);

        return $setting->holds->either(
            shown: static fn(string $value): WhatOneSettingSays => new WhatOneSettingSays(
                key: $setting->key,
                said: $value,
                withheld: false,
                mayBeChanged: true,
                from: $from,
            ),
            // A withheld value is a credential, and this app does not offer to
            // set one. Decided in the same fold that decided what to print, so
            // there is no second place holding an opinion about which settings
            // are sensitive — and no way to add a control to this arm without
            // reading the sentence that says why not.
            withheld: static fn(string $note): WhatOneSettingSays => new WhatOneSettingSays(
                key: $setting->key,
                said: $note,
                withheld: true,
                mayBeChanged: false,
                from: $from,
            ),
        );
    }
}
