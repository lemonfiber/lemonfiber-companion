<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Operator\Internal\ViewModels\WhatOneSettingSays;
use Modules\Operator\Internal\ViewModels\WhereARowSaysItCameFrom;

/**
 * One setting, turned into the row a template draws.
 *
 * The fold is the only way into {@see \Modules\Kernel\Api\WhatASettingHolds},
 * so this is the single place in the app that decides what a withheld value
 * looks like — and it decides it by carrying the stack's note through
 * unchanged.
 *
 * **The origin is folded here too, and every row gets one.** It is required
 * wherever a setting is shown, and a presenter that only filled it in for the
 * interesting arms would leave the common case — a stack default — reading as
 * a row whose origin nobody established. Those are the two the rules most want
 * kept apart, so all four arms produce a key and the template has no branch to
 * get wrong.
 */
final readonly class HowASettingReads
{
    public function in(Setting $setting): WhatOneSettingSays
    {
        $from = $setting->from->whichever(
            bundled: static fn(): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Bundled),
            operator: static fn(): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Operator),
            plugin: static fn(string $named): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Plugin, $named),
            unknown: static fn(string $why): WhereARowSaysItCameFrom => new WhereARowSaysItCameFrom(WhoSetIt::Unknown, $why),
        );

        return $setting->holds->either(
            shown: static fn(string $value): WhatOneSettingSays => new WhatOneSettingSays(
                key: $setting->key,
                said: $value,
                withheld: false,
                mayBeChanged: true,
                came: $from->came,
                attributed: $from->attributed,
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
                came: $from->came,
                attributed: $from->attributed,
            ),
        );
    }
}
