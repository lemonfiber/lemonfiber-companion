<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Operator\Internal\ViewModels\AProfileLeftOutAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatStartingItWouldShow;

/** What starting a form would come to, as the template draws it before the verbs. */
final readonly class HowARehearsalReads
{
    /** The stack's rehearsal, service by service and profile by profile. */
    public function of(WhatStartingItWouldComeTo $rehearsal): WhatStartingItWouldShow
    {
        $wouldStart = [];

        foreach ($rehearsal->wouldStart() as $service) {
            $wouldStart[] = $service->named();
        }

        $leftOut = [];

        foreach ($rehearsal->leftOut() as $profile) {
            $leftOut[] = new AProfileLeftOutAsShown($profile->profile(), $profile->needs()->saidOnTheScreen());
        }

        return new WhatStartingItWouldShow(HowTheReadingWent::itCameBack(), $wouldStart, $leftOut);
    }

    /** Asking for it met this instead. */
    public function met(Obstacle $why): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::somethingStopped($why), [], []);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::theSessionEnded(), [], []);
    }
}
