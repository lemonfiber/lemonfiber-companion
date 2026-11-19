<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheWaysOfDownloading;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;

/**
 * Turns what setup settled into what a stack's settings and its own screen draw.
 */
final readonly class HowWhatSetupSettledReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): WhatSetupSettledAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): WhatSetupSettledAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said what setup settled. */
    public function this(WhatSetupSettled $settled): WhatSetupSettledAsShown
    {
        return new WhatSetupSettledAsShown(
            went: HowTheReadingWent::itCameBack(),
            finished: $settled->setup()->isDone(),
            dataRoot: $settled->dataRoot()->path(),
            downloadingSaid: $this->downloading($settled->downloading()),
            runsAs: $settled->serviceUser()->asTyped(),
            runsAsFromSaid: $settled->serviceUser()->from()->saidOnTheScreen(),
        );
    }

    private function downloading(TheWaysOfDownloading $ways): string
    {
        return match (true) {
            $ways->usenet() && $ways->torrent() => 'settled.downloading.both',
            $ways->usenet() => 'settled.downloading.usenet',
            $ways->torrent() => 'settled.downloading.torrent',
            default => 'settled.downloading.neither',
        };
    }

    private function nothing(HowTheReadingWent $went): WhatSetupSettledAsShown
    {
        return new WhatSetupSettledAsShown($went, finished: false, dataRoot: '', downloadingSaid: '', runsAs: '', runsAsFromSaid: '');
    }
}
