<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatStartingItWouldShow;

/** What starting a form would come to, as the template draws it before the verbs. */
final readonly class HowARehearsalReads
{
    /** The stack's rehearsal, service by service, with its estimate. */
    public function of(WhatStartingItWouldComeTo $rehearsal): WhatStartingItWouldShow
    {
        $names = new HowWhatWasLeftOutReads();

        return new WhatStartingItWouldShow(
            HowTheReadingWent::itCameBack(),
            $names->services($rehearsal->wouldStart()),
            $names->of($rehearsal->leftOut()),
            $rehearsal->footprint()->mebibytes(),
            $names->services($rehearsal->footprint()->unestimated()),
        );
    }

    /** Asking for it met this instead. */
    public function met(Obstacle $why): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::somethingStopped($why), [], [], 0, []);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::theSessionEnded(), [], [], 0, []);
    }
}
