<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Operator\Internal\ViewModels\WhatOneOutcomeSays;

/**
 * What became of one repair, turned into the fields a row reads.
 *
 * **Two arms that differ in one field, rather than one arm and a test for
 * emptiness.** {@see LeftBehind} answers through an `either()` so that *it left
 * something* and *it left nothing* are told apart by the value rather than by
 * reading a string and guessing, and the fold spends both arms to keep it that
 * way: the empty string a row carries for *nothing was left* is written in the
 * arm that means it, and nowhere else.
 */
final readonly class HowAnOutcomeReads
{
    /** Fold one outcome into the fields a row needs. */
    public function in(Mended $mended): WhatOneOutcomeSays
    {
        return $mended->said(
            static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): WhatOneOutcomeSays => $left->either(
                something: static fn(string $what): WhatOneOutcomeSays => new WhatOneOutcomeSays(
                    repair: new HowARepairReads()->in($repair),
                    became: $became->saidOnTheScreen(),
                    left: $what,
                    worthAnotherGo: $became->worthAnotherGo(),
                ),
                nothing: static fn(): WhatOneOutcomeSays => new WhatOneOutcomeSays(
                    repair: new HowARepairReads()->in($repair),
                    became: $became->saidOnTheScreen(),
                    left: '',
                    worthAnotherGo: $became->worthAnotherGo(),
                ),
            ),
        );
    }
}
