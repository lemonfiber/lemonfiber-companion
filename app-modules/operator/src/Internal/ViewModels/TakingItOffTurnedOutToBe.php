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
     * @param WhereTheWorkStands              $stands            where the work stands, and whether the stack could be asked at all
     * @param bool                           $chosen          whether a removal is being read, rather than the four offered to choose from
     * @param bool                           $wasAgreed       whether a yes was sent, so what could not be read is whether it happened
     * @param string                         $refusal         the stack's reason for refusing, or empty
     * @param bool                           $endsThisSession whether a yes was given to what takes this app's way in, so its session has ended or will
     * @param TheReadingAsShown|null         $reading         the reading, where one came back
     * @param WhatTakingItOffDidAsShown|null $did             what the removal did, where it was agreed to and came back
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $chosen,
        public bool $wasAgreed,
        public string $refusal,
        public bool $endsThisSession,
        public ?TheReadingAsShown $reading,
        public ?WhatTakingItOffDidAsShown $did,
    ) {}
}
