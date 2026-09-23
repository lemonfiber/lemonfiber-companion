<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack for its record produced, flattened for a template.
 *
 * The sibling of {@see WhatKeepsRunningTurnedOutToBe}, written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowTheRecordReads} folds the
 * answer once and the template reads fields.
 *
 * **The horizon is set whenever the stack answered**, and the template draws it
 * where the list ends whether or not there are changes above it. An empty record drawn without
 * saying how far back it reaches reads as *nothing has ever happened here*,
 * which is the one reading the horizon exists to prevent.
 */
final readonly class TheRecordTurnedOutToBe
{
    /**
     * @param string                   $horizon how far back the record goes, or empty where nothing answered
     * @param list<AMomentOnTheRecord> $moments every moment, newest first, in the stack's order
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $horizon,
        public array $moments,
    ) {}
}
