<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where a walkthrough stopped: the step, why, the one thing to try, and what the services were saying.
 *
 * The logs travel with it because a fault report the operator has to go and
 * research is one they abandon.
 */
final readonly class WhereItStopped
{
    private function __construct(
        private WalkthroughStep $step,
        private WhyTheWalkthroughStopped $why,
        private string $remedy,
        private WhatTheServicesWereSaying $logs,
    ) {}

    /** A stop at a step, for a reason; a blank remedy is refused. */
    public static function at(WalkthroughStep $step, WhyTheWalkthroughStopped $why, string $remedy, WhatTheServicesWereSaying $logs): self
    {
        if (trim($remedy) === '') {
            throw TheWalkthroughSaysNothing::about('remedy');
        }

        return new self($step, $why, $remedy, $logs);
    }

    public function step(): WalkthroughStep
    {
        return $this->step;
    }

    public function why(): WhyTheWalkthroughStopped
    {
        return $this->why;
    }

    /** The one thing to try, in the stack's words. */
    public function remedy(): string
    {
        return $this->remedy;
    }

    public function logs(): WhatTheServicesWereSaying
    {
        return $this->logs;
    }
}
