<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * What asking a stack about its last start produced, flattened for the restart section.
 *
 * `$check` is said by its own `whenSaid`: the check after the latest restart,
 * or an earlier one, so an earlier check is never drawn as passing for the
 * latest restart.
 */
final readonly class TheLastStartAsShown
{
    /**
     * @param HowTheReadingWent        $went        whether the stack answered, and what stopped it where not
     * @param string                   $startSaid   the key for when the machine last started and whether a check ran since, or for its not saying
     * @param AgoAsShown               $startedAgo  how long ago it started, where it said
     * @param ACheckAfterAStartAsShown $check       the check to draw, which says which it is
     * @param string                   $declined    the stack's words for why no check ran after the latest start, or empty
     * @param WhatFailingCameTo        $failing     how long it has failed to come back
     * @param bool                     $nothingKept whether the stack keeps no record of a start at all
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $startSaid,
        public AgoAsShown $startedAgo,
        public ACheckAfterAStartAsShown $check,
        public string $declined,
        public WhatFailingCameTo $failing,
        public bool $nothingKept,
    ) {}
}
