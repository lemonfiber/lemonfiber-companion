<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\OneEventSetApart;
use Modules\Operator\Internal\ViewModels\WhatIsToldTurnedOutToBe;

/**
 * What asking a stack what its operator is told about produces, as screen fields.
 *
 * `F2` — data in, view model out.
 */
final readonly class HowWhatIsToldReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): WhatIsToldTurnedOutToBe
    {
        return new WhatIsToldTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), preset: '', means: '', exceptions: []);
    }

    /** The stack answered, and this is what its operator is told about. */
    public function this(WhatTheOperatorIsTold $told): WhatIsToldTurnedOutToBe
    {
        $exceptions = [];

        foreach ($told->exceptions() as $event) {
            $exceptions[] = new OneEventSetApart(kind: $event->kind(), heardSaid: $event->heard()->saidOnTheScreen());
        }

        return new WhatIsToldTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            preset: $told->preset(),
            means: $told->means(),
            exceptions: $exceptions,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): WhatIsToldTurnedOutToBe
    {
        return new WhatIsToldTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), preset: '', means: '', exceptions: []);
    }
}
