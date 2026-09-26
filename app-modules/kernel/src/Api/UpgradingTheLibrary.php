<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Fetching better copies of what is already in the library, asked of the stack.
 *
 * Apart from {@see ChoosingQuality} because it is a different act with a
 * different cost: a preset shapes what is fetched next, and this fetches the
 * library again. Two methods, because the wire's one action unconfirmed only
 * describes and confirmed carries out, and the second takes an
 * {@see AnUpgradeDescribed} that only the first's answer makes.
 */
interface UpgradingTheLibrary
{
    /**
     * What upgrading would come to, kind by kind, with nothing fetched.
     *
     * Answers {@see WhatTheUpgradeCameTo} rather than raising, which `C1`
     * requires.
     */
    public function whatItWouldComeTo(Stack $stack, Session $session): WhatTheUpgradeCameTo;

    /** Carry it out, having been shown what it would come to. */
    public function upgrade(Stack $stack, Session $session, AnUpgradeDescribed $agreed): WhatTheUpgradeCameTo;
}
