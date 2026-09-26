<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What taking a copy came to, as the stack reported it.
 *
 * What it covered, the older copies it removed to make room, how its size
 * stood against the time a copy is meant to take, whether it holds
 * credentials, and whether it was a rehearsal. The removed copies are part of
 * the answer rather than something to find out later from a list that is
 * shorter than it was.
 */
final readonly class ACopyTaken
{
    private function __construct(
        private ScopeOfACopy $scope,
        private TheCopies $pruned,
        private HowACopyPaced $pace,
        private WhetherItHoldsASecret $sensitive,
        private WhetherItWasRehearsed $rehearsed,
    ) {}

    /** The stack's report of one copy. */
    public static function reported(
        ScopeOfACopy $scope,
        TheCopies $pruned,
        HowACopyPaced $pace,
        WhetherItHoldsASecret $sensitive,
        WhetherItWasRehearsed $rehearsed,
    ): self {
        return new self($scope, $pruned, $pace, $sensitive, $rehearsed);
    }

    /** What it covered. */
    public function scope(): ScopeOfACopy
    {
        return $this->scope;
    }

    /** The older copies it removed, by name, which may be none. */
    public function pruned(): TheCopies
    {
        return $this->pruned;
    }

    /** How its size stood against what a copy is reckoned to manage in a minute. */
    public function pace(): HowACopyPaced
    {
        return $this->pace;
    }

    /** Whether it holds credentials, and so has to be kept as privately as they are. */
    public function holds(): WhetherItHoldsASecret
    {
        return $this->sensitive;
    }

    /** Whether it happened, or was run to find out what would. */
    public function was(): WhetherItWasRehearsed
    {
        return $this->rehearsed;
    }
}
