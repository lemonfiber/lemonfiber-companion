<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** How a guard ended: in words of its own, the stack's words for why where it gave any, and what it stopped where it saw the data location go. */
final readonly class HowAGuardEnded
{
    /**
     * @param string                   $said    the catalogue key for how it ended
     * @param string|null              $why     the stack's own words for why it ended or why it did not start, where it said
     * @param WhatTheGuardStopped|null $stopped whether stopping the forms worked, and the forms it named, where it saw the data location go
     */
    public function __construct(public string $said, public ?string $why = null, public ?WhatTheGuardStopped $stopped = null) {}
}
