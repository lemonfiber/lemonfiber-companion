<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the walkthrough started from this screen, flattened for the template.
 *
 * Each state carries only its own: a walk still running has no lines, and one
 * nobody started has only the road a walk takes.
 */
final readonly class WhatTheWalkthroughTurnedOutToBe
{
    /**
     * @param HowTheReadingWent             $went       whether asking after it came back, and what stood in the way where it did not
     * @param bool                          $wasStarted whether a walkthrough was started here, so there is anything to follow
     * @param bool                          $isWorking  whether the stack is still walking it
     * @param bool                          $hasEnded   whether the stack has no outcome for it any more
     * @param TheWalkthroughAsRecorded|null $record     what it reported once it finished, or null before then
     * @param list<string>                  $road       the steps a walk takes, as the stack names them, before one is started; empty after
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $wasStarted,
        public bool $isWorking,
        public bool $hasEnded,
        public ?TheWalkthroughAsRecorded $record,
        public array $road,
    ) {}
}
