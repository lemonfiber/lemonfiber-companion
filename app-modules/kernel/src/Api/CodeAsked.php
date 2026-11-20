<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking a stack for a sign-in code: the code, the house's word that it signs in by password, or what stood in the way.
 */
final readonly class CodeAsked
{
    private function __construct(
        private ACodeToApprove|WhatTheHouseholdWasTold|Obstacle $outcome,
        private WhenToTryAgain $tryAgain,
    ) {}

    /** The stack issued a code. */
    public static function issued(ACodeToApprove $code): self
    {
        return new self($code, WhenToTryAgain::unsaid());
    }

    /** The house signs its members in by password, in its own words. */
    public static function byPassword(WhatTheHouseholdWasTold $told): self
    {
        return new self($told, WhenToTryAgain::unsaid());
    }

    /** Something stood in the way, with when to ask again where the stack said. */
    public static function refused(Obstacle $why, WhenToTryAgain $tryAgain): self
    {
        return new self($why, $tryAgain);
    }

    /**
     * @template T of object
     *
     * @param Closure(ACodeToApprove): T             $issued
     * @param Closure(WhatTheHouseholdWasTold): T    $byPassword
     * @param Closure(Obstacle, WhenToTryAgain): T   $refused
     *
     * @return T
     */
    public function either(Closure $issued, Closure $byPassword, Closure $refused): object
    {
        return match (true) {
            $this->outcome instanceof ACodeToApprove => $issued($this->outcome),
            $this->outcome instanceof WhatTheHouseholdWasTold => $byPassword($this->outcome),
            default => $refused($this->outcome, $this->tryAgain),
        };
    }
}
