<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What an action that stops services would interrupt, as the question before it draws it.
 */
final readonly class WhatIsComingDownAsShown
{
    /**
     * @param bool                                 $found  whether the stack could be asked
     * @param list<ADownloadAStopInterruptsAsShown> $coming each download still coming down, in the stack's order
     */
    public function __construct(
        public bool $found,
        public array $coming,
    ) {}

    /** Whether the yes is offered as two: letting them finish, and going ahead now. */
    public function offersWaiting(): bool
    {
        return $this->coming !== [];
    }
}
