<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Operator\Internal\ViewModels\WhatTheStackWouldPutRight;

/**
 * What asking a stack what it would put right produces, as fields a template
 * reads.
 *
 * `F2`: data in, view model out. Nothing is injected and nothing is asked of
 * the outside, so a test states a listing or an obstacle and reads a screen
 * rather than arranging a port and hoping.
 *
 * **A credential the stack refused is a signed-out app, not an obstacle.**
 * `N3-R13` says an identity removed from the household results in a
 * signed-out app at the next refused call and that nothing already loaded
 * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
 * line once, so this fold and every one beside it cannot come to disagree
 * about whether somebody is signed in.
 */
final readonly class HowAnOfferOfRepairsReads
{
    /** No session for that stack, so nothing was asked (`N1-R44`). */
    public function signedOut(): WhatTheStackWouldPutRight
    {
        return new WhatTheStackWouldPutRight(isSignedIn: false);
    }

    /** The stack is still working out what it would do. */
    public function stillWorkingItOut(): WhatTheStackWouldPutRight
    {
        return new WhatTheStackWouldPutRight(isWorking: true);
    }

    /** It finished, and this is the listing. */
    public function offering(Offer $offer): WhatTheStackWouldPutRight
    {
        $rows = [];

        foreach ($offer->repairs() as $repair) {
            $rows[] = new HowARepairReads()->in($repair);
        }

        // The listing's name is carried even though no template shows it.
        // `N2-R6` has a yes quote the listing it was given, and the screen that
        // will offer that yes reads it from here — a fold that dropped it would
        // have to ask the stack again to agree to what it is already showing.
        return new WhatTheStackWouldPutRight(named: $offer->named(), repairs: $rows);
    }

    /** The stack has no outcome for that job any more. */
    public function ended(): WhatTheStackWouldPutRight
    {
        return new WhatTheStackWouldPutRight(hasEnded: true);
    }

    /** The machine could not be reached, and this is what the operator met. */
    public function met(Obstacle $why): WhatTheStackWouldPutRight
    {
        if ($why->meansWeAreSignedOut()) {
            return $this->signedOut();
        }

        return new WhatTheStackWouldPutRight(met: $why->said(), remedy: $why->remedy());
    }
}
