<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What putting one run back would take with it, flattened for a template.
 *
 * Drawn from the record's own rows for the run and nothing else: the stack
 * offers no rehearsal of this, so the agreement is what the record already
 * says. Nothing here describes something that has happened.
 */
final readonly class WhatPuttingARunBackWouldShow
{
    /**
     * @param HowTheReadingWent               $went          whether the record came back, and what stood in the way where it did not
     * @param bool                            $namesARun     whether the screen was opened on a run at all
     * @param bool                            $isOnTheRecord whether the record holds any change under the run's stamp
     * @param bool                            $goesBack      whether the record's rows say the whole run can go back
     * @param string                          $whenSaid      the key for when the run was made, or for the clock not saying
     * @param int                             $whenCount     how many of that unit, where there is a unit
     * @param int                             $alongside     how many changes go with the run, as the record's row says
     * @param list<WhatOneRecordedChangeSays> $changes       every change the record holds under the stamp, in its order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $namesARun,
        public bool $isOnTheRecord,
        public bool $goesBack,
        public string $whenSaid,
        public int $whenCount,
        public int $alongside,
        public array $changes,
    ) {}
}
