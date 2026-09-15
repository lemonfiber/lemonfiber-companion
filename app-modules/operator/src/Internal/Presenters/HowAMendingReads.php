<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Operator\Internal\ViewModels\WhatThisStackPutRight;

/**
 * What carrying out an agreement produces, as the fields a template reads.
 *
 * `F2`: data in, view model out. What the stack answered is handed in, so a
 * test states an outcome and reads a screen rather than standing a machine up
 * behind a port first.
 *
 * **The ended arm is the one that carries the weight here.** After an offer, a
 * job the stack has forgotten costs a second question. After an agreement it
 * means the operator does not know what happened to their machine — and *it
 * failed* is the one answer that is certainly wrong, because it may well have
 * worked. The remedy is to look at the machine's health, and never to agree
 * again: that would be asking a stack to repeat work nobody can confirm it did
 * not already do.
 */
final readonly class HowAMendingReads
{
    /** The stack is still carrying out what it was agreed to. */
    public function stillWorkingItOut(): WhatThisStackPutRight
    {
        return new WhatThisStackPutRight(isWorking: true);
    }

    /** It finished, and this is what became of each repair. */
    public function these(WhatWasMended $mended): WhatThisStackPutRight
    {
        $rows = [];

        foreach ($mended as $one) {
            $rows[] = new HowAnOutcomeReads()->in($one);
        }

        // The count comes off the collection rather than off the rows, so the
        // line between *something happened* and *nothing did* is drawn once —
        // by `WhatBecameOfIt` — and this screen cannot come to disagree with
        // another about whether a run did anything.
        return new WhatThisStackPutRight(outcomes: $rows, changed: $mended->changed());
    }

    /** The stack has no outcome for that job any more. */
    public function ended(): WhatThisStackPutRight
    {
        return new WhatThisStackPutRight(hasEnded: true);
    }

    /** The machine could not be reached, and this is what the operator met. */
    public function met(Obstacle $why): WhatThisStackPutRight
    {
        // `N3-R13`: a refused credential is a signed-out app rather than a
        // sentence about a machine, and the screen must not go on rendering
        // what it loaded before. Said as a flag rather than by returning an
        // empty outcome, because the outcome is not the thing that changed —
        // this device's standing with the stack is.
        if ($why->meansWeAreSignedOut()) {
            return new WhatThisStackPutRight(isSignedOut: true);
        }

        return new WhatThisStackPutRight(met: $why->said(), remedy: $why->remedy());
    }
}
