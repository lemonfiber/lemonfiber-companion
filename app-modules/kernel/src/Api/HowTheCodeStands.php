<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack answered about a sign-in code: a session once it was approved, that it still waits, that it lapsed, or what stood in the way.
 */
final readonly class HowTheCodeStands
{
    private function __construct(
        private Opening|Instant|WhatTheHouseholdWasTold|Obstacle $outcome,
        private WhenToTryAgain $tryAgain,
    ) {}

    /** The code was approved, and the stack opened the member's session. */
    public static function opened(Session $session, Instant $until, Whose $whose): self
    {
        return new self(new Opening($session, $until, $whose), WhenToTryAgain::unsaid());
    }

    /** The code is not approved yet, and lapses then. */
    public static function waiting(Instant $lapses): self
    {
        return new self($lapses, WhenToTryAgain::unsaid());
    }

    /** The stack no longer waits on the code, in its own words. */
    public static function lapsed(WhatTheHouseholdWasTold $told): self
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
     * @param Closure(Session, Instant, Whose): T   $opened
     * @param Closure(Instant): T                   $waiting
     * @param Closure(WhatTheHouseholdWasTold): T   $lapsed
     * @param Closure(Obstacle, WhenToTryAgain): T  $refused
     *
     * @return T
     */
    public function either(Closure $opened, Closure $waiting, Closure $lapsed, Closure $refused): object
    {
        return match (true) {
            $this->outcome instanceof Opening => $opened($this->outcome->session, $this->outcome->until, $this->outcome->whose),
            $this->outcome instanceof Instant => $waiting($this->outcome),
            $this->outcome instanceof WhatTheHouseholdWasTold => $lapsed($this->outcome),
            default => $refused($this->outcome, $this->tryAgain),
        };
    }
}
