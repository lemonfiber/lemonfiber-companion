<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatIsAlreadyRunning;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Operator\Internal\ViewModels\AServiceToStartAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatStartingItWouldShow;

/** What starting a form would come to, as the template draws it before the verbs. */
final readonly class HowARehearsalReads
{
    private const string WOULD_START = 'health.rehearsal.would_start';

    private const string ALREADY_RUNNING = 'health.rehearsal.already_running';

    /** The stack's rehearsal, service by service, with its estimate. */
    public function of(WhatStartingItWouldComeTo $rehearsal): WhatStartingItWouldShow
    {
        $names = new HowWhatWasLeftOutReads();

        return new WhatStartingItWouldShow(
            HowTheReadingWent::itCameBack(),
            $this->toStart($rehearsal->wouldStart(), $rehearsal->alreadyRunning()),
            $names->of($rehearsal->leftOut()),
            $rehearsal->footprint()->mebibytes(),
            $names->services($rehearsal->footprint()->unestimated()),
            runningUnread: ! $rehearsal->alreadyRunning()->wasRead(),
        );
    }

    /** Asking for it met this instead. */
    public function met(Obstacle $why): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::somethingStopped($why), [], [], null, [], runningUnread: false);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): WhatStartingItWouldShow
    {
        return new WhatStartingItWouldShow(HowTheReadingWent::theSessionEnded(), [], [], null, [], runningUnread: false);
    }

    /**
     * Each service a start names, said as one it would bring up or as one already running.
     *
     * @return list<AServiceToStartAsShown>
     */
    private function toStart(Services $wouldStart, WhatIsAlreadyRunning $running): array
    {
        $rows = [];

        foreach ($wouldStart as $service) {
            $rows[] = new AServiceToStartAsShown(
                $service->named(),
                $running->holds($service) ? self::ALREADY_RUNNING : self::WOULD_START,
            );
        }

        return $rows;
    }
}
