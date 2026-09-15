<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SomethingElseRunning;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Operator\Internal\ViewModels\WhatElseTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatOneOtherContainerSays;

/**
 * What a screen says about containers this stack never declared.
 *
 * `N2-R21` asks for two things of each — a name, and what it is running — and
 * forbids a third: presenting one as part of the stack or offering a verb
 * against it. The first two are folded here. The third is not enforced here at
 * all, and deliberately so: {@see SomethingElseRunning} carries a
 * {@see \Modules\Kernel\Api\WhatTheEngineCallsIt} rather than a `ServiceId`, so
 * there is no name of the kind a verb accepts for this presenter to hand on. A
 * rule kept by the shape of the value survives a rewrite of this class; one
 * kept by remembering does not.
 */
final readonly class HowSomethingElseReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * Nothing was met, because the app did not get as far as asking — the empty
     * keys are what the template branches on.
     */
    public function signedOut(): WhatElseTurnedOutToBe
    {
        return new WhatElseTurnedOutToBe(
            isSignedIn: false,
            met: '',
            remedy: '',
            running: [],
        );
    }

    /** The machine answered, and this is what it is running that nobody declared. */
    public function these(WhatElseIsRunning $running): WhatElseTurnedOutToBe
    {
        $rows = [];

        foreach ($running as $one) {
            $rows[] = $this->of($one);
        }

        return new WhatElseTurnedOutToBe(
            isSignedIn: true,
            met: '',
            remedy: '',
            running: $rows,
        );
    }

    /**
     * It did not, and this is what the operator met instead.
     *
     * A refused credential is being signed out rather than an obstacle, which
     * is one decision this app makes in one place — {@see Obstacle} owns it,
     * and a presenter re-deciding it is how two screens come to disagree about
     * what a stack just said.
     */
    public function met(Obstacle $why): WhatElseTurnedOutToBe
    {
        if ($why->meansWeAreSignedOut()) {
            return $this->signedOut();
        }

        return new WhatElseTurnedOutToBe(
            isSignedIn: true,
            met: $why->said(),
            remedy: $why->remedy(),
            running: [],
        );
    }

    /** One container, as the machine described it. */
    private function of(SomethingElseRunning $one): WhatOneOtherContainerSays
    {
        return new WhatOneOtherContainerSays(
            named: $one->id->named(),
            describes: $one->describes,
            runs: $one->runs->saidOnTheScreen(),
        );
    }
}
