<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Findings;

/**
 * One frame's worth of answer, flattened for a template to read.
 *
 * `WhatCameBack::either()` and `Resumed::either()` both answer with an object,
 * so that a caller cannot take a report or a session out without saying what
 * happens when there is none. That is the right shape for a port and the wrong
 * one for a Blade file, which has no `either()` and cannot be given one — so
 * {@see \Modules\Operator\Internal\Presenters\HowAStackReads} folds three
 * outcomes into this, and every accessor reads it.
 *
 * **Three outcomes, not two.** The session has ended, the stack was asked and
 * answered, or it was asked and the operator met something. Signed-out is its
 * own state rather than an obstacle because the remedy is a different screen:
 * `N1-R44` sends them to sign in, where every obstacle sends them to look at
 * the machine. The empty keys are what the template branches on.
 *
 * `Internal` because it is a detail of how this surface reads two outcomes, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatTheStackTurnedOutToBe
{
    /**
     * @param string   $overall  the key for the headline, or empty where nothing ran
     * @param Findings $findings what the checks produced, empty where they did not run
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $overall,
        public Findings $findings,
    ) {}
}
