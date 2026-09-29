<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_key_exists;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\StackId;

/**
 * What the list has heard from each stack it listens to, one subscription apiece.
 *
 * {@see WhatWasHeardSoFar} per stack, so each stack's subscription keeps the
 * rules the stack's own screen keeps: silence past the contract's bound breaks
 * it, and a broken one waits out its own break before it is opened again. A
 * stack that could not be heard does not hold up any other.
 *
 * A stack the list does not listen to has no entry, which is how a stack with
 * no operator session stays out of it.
 */
final readonly class WhatEachStackSaidSoFar
{
    /** @param array<string, WhatWasHeardSoFar> $heard by each stack's stored identifier */
    private function __construct(private array $heard) {}

    /** A list that has listened to nothing yet. */
    public static function nothingYet(): self
    {
        return new self([]);
    }

    /** What has been heard from this stack, or nothing where it has not been listened to. */
    public function from(StackId $stack): WhatWasHeardSoFar
    {
        $which = $stack->stored();

        return array_key_exists($which, $this->heard) ? $this->heard[$which] : WhatWasHeardSoFar::nothingYet();
    }

    /** The same, with what this stack's subscription holds now. */
    public function with(StackId $stack, WhatWasHeardSoFar $heard): self
    {
        $each = $this->heard;
        $each[$stack->stored()] = $heard;

        return new self($each);
    }

    /** Every subscription let go of, because nobody can see the list. */
    public function wentAway(): self
    {
        $gone = [];

        foreach ($this->heard as $which => $heard) {
            $gone[$which] = $heard->wentAway();
        }

        return new self($gone);
    }
}
