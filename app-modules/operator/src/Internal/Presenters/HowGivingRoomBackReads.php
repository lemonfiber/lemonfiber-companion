<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\HowGivingItBackWent;
use Modules\Operator\Internal\ViewModels\WhatGivingItBackWouldShow;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Giving back one category of the room on offer, as the template draws it: the offer first, and what became of the yes after.
 *
 * Two families of method for two values, so an offer and a report of what
 * was reclaimed never share a model. The category on the offer is drawn by
 * {@see HowTheRoomReads}, in the words Storage lists it in.
 */
final readonly class HowGivingRoomBackReads
{
    /** The category as the stack's offer holds it, to agree to. */
    public function offering(ACategoryOffered $category): WhatGivingItBackWouldShow
    {
        return new WhatGivingItBackWouldShow(
            stands: WhereTheWorkStands::answered(),
            namesACategory: true,
            category: new HowTheRoomReads()->category($category),
        );
    }

    /** The stack's offer holds none of the category, so there is nothing to agree to. */
    public function notOnOffer(): WhatGivingItBackWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::answered());
    }

    /** Reading the offer met this instead. */
    public function notOffered(Obstacle $why): WhatGivingItBackWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::met($why));
    }

    /** This device no longer holds a session for the stack, so nothing was read. */
    public function notAsked(): WhatGivingItBackWouldShow
    {
        return $this->nothingOffered(WhereTheWorkStands::signedOut());
    }

    /** The screen was opened naming no category the stack gives, so there is nothing to read. */
    public function namesNoCategory(): WhatGivingItBackWouldShow
    {
        return new WhatGivingItBackWouldShow(stands: WhereTheWorkStands::answered(), namesACategory: false, category: null);
    }

    /** The stack is still giving it back. */
    public function running(): HowGivingItBackWent
    {
        return $this->following(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(?WhyTheWorkEnded $why): HowGivingItBackWent
    {
        return $this->following(WhereTheWorkStands::ended($why));
    }

    /** Giving it back, or asking after it, met this instead. */
    public function met(Obstacle $why): HowGivingItBackWent
    {
        return $this->following(WhereTheWorkStands::met($why));
    }

    /** This device no longer holds a session for the stack it was given back on. */
    public function signedOut(): HowGivingItBackWent
    {
        return $this->following(WhereTheWorkStands::signedOut());
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): HowGivingItBackWent
    {
        return new HowGivingItBackWent(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
            returned: null,
            went: 0,
            left: [],
        );
    }

    /** The stack's report of what it reclaimed: the room returned, how many items went, and why each one left behind stayed. */
    public function done(TheRoomGivenBack $report): HowGivingItBackWent
    {
        $big = HowBig::of($report->bytes());
        $left = [];

        foreach ($report->left() as $why) {
            $left[] = $why;
        }

        return new HowGivingItBackWent(
            stands: WhereTheWorkStands::answered(),
            refused: null,
            returned: new ASizeAsShown(figure: $big->figure, unit: $big->said),
            went: $report->went(),
            left: $left,
        );
    }

    /** An offer with nothing in it to agree to. */
    private function nothingOffered(WhereTheWorkStands $stands): WhatGivingItBackWouldShow
    {
        return new WhatGivingItBackWouldShow(stands: $stands, namesACategory: true, category: null);
    }

    /** A state with no report to draw. */
    private function following(WhereTheWorkStands $stands): HowGivingItBackWent
    {
        return new HowGivingItBackWent(stands: $stands, refused: null, returned: null, went: 0, left: []);
    }
}
