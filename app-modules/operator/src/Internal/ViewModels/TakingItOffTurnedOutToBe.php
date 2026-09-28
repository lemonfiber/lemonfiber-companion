<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where taking lemonfiber off has got to, flattened for the template.
 *
 * One model for every state the screen can be in, each saying only its own:
 * the four to choose from, a reading to agree to, the stack working, what the
 * removal did, the stack refusing with its reason, or the work ended with no
 * outcome.
 */
final readonly class TakingItOffTurnedOutToBe
{
    /**
     * @param HowTheReadingWent              $went            whether the stack was reached, and what stood in the way where it was not
     * @param bool                           $chosen          whether a removal is being read, rather than the four offered to choose from
     * @param bool                           $wasAgreed       whether a yes was sent, so what could not be read is whether it happened
     * @param bool                           $isWorking       whether the stack is still taking it off
     * @param bool                           $hasEnded        whether the stack has no outcome for it any more
     * @param string                         $refusal         the stack's reason for refusing, or empty
     * @param bool                           $endsThisSession whether a yes was given to what takes this app's way in, so its session has ended or will
     * @param TheReadingAsShown|null         $reading         the reading, where one came back
     * @param WhatTakingItOffDidAsShown|null $did             what the removal did, where it was agreed to and came back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $chosen,
        public bool $wasAgreed,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refusal,
        public bool $endsThisSession,
        public ?TheReadingAsShown $reading,
        public ?WhatTakingItOffDidAsShown $did,
    ) {}
}
