<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheStackTurnedOutToBe;

/**
 * What asking a stack how it is produces, as the fields a template reads.
 *
 * `F2`: data in, view model out. Nothing here is injected, nothing here is
 * asked of the outside, and the three methods are the three things that can
 * come back from one asking — so a test states an obstacle and reads a screen,
 * rather than arranging a port and hoping.
 *
 * **A credential the stack refused is a signed-out app, not an obstacle.**
 * An identity removed from the household results in a signed-out
 * app at the next refused call and that nothing already loaded goes on being
 * rendered. {@see Obstacle::meansWeAreSignedOut()} draws that line once, so
 * this presenter and every one beside it cannot come to disagree about whether
 * somebody is signed in.
 */
final readonly class HowAStackReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes.
     */
    public function signedOut(): WhatTheStackTurnedOutToBe
    {
        return new WhatTheStackTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            overall: '',
            findings: Findings::none(),
        );
    }

    /** The stack answered, and this is what it said. */
    public function said(Report $report): WhatTheStackTurnedOutToBe
    {
        return new WhatTheStackTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            overall: $report->overall()->saidOnTheScreen(),
            findings: $report->findings(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle} rather than being spelled, which is the
     * same derivation every other screen in this application uses — and it is
     * why an obstacle gaining a seventh case needs no edit here.
     */
    public function met(Obstacle $why): WhatTheStackTurnedOutToBe
    {
        return new WhatTheStackTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            overall: '',
            findings: Findings::none(),
        );
    }
}
