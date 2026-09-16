<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\WhatToDoWithIt;

/**
 * The one thing a screen about a single service or form is about.
 *
 * `N2-R7` has two granularities and an operator choosing between them is doing
 * the same thing either way, so one value covers both and says which it is.
 * Folded rather than handed out a field at a time, because an accessor per
 * field is what takes a screen past `H3`'s twenty — and because the four
 * answers here are one answer: *what did the route name, and what may be done
 * with it*.
 *
 * **A thing this stack does not run is a real case and is spelled as one.** A
 * route can say anything: a tap from a list the machine has since changed, or a
 * screen restored after the stack forgot a service. Neither a service nor a
 * form, with no verbs, is what that comes out as — which the template says
 * plainly, because a blank frame reads as a screen that failed to draw.
 */
final readonly class WhatOneThingIs
{
    /** @param list<WhatToDoWithIt> $verbs what the thing may be told to do */
    public function __construct(
        public string $named,
        public bool $isAForm = false,
        public array $verbs = [],
        public ?WhatOneServiceSays $service = null,
    ) {}

    /** Whether the machine is running anything of that name at all. */
    public function isRun(): bool
    {
        return $this->isAForm || $this->service instanceof WhatOneServiceSays;
    }
}
