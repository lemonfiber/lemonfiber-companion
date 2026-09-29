<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking a stack to rehearse putting a copy back came away with.
 *
 * The listing, the stack's refusal to list it, or what stood in the way. A
 * stack that refused to list a copy — one written by a newer lemonfiber, one
 * it does not manage, one that is corrupt — says why in its own words, and
 * nothing can be agreed to off either of the last two.
 */
final readonly class WhatTheRestoreRehearsalFound
{
    private function __construct(private WhatPuttingItBackWouldDo|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack listed the copy, and this is what putting it back would do. */
    public static function listed(WhatPuttingItBackWouldDo $listing): self
    {
        return new self($listing);
    }

    /** The stack would not list it, and this is why. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TListed of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(WhatPuttingItBackWouldDo): TListed $listed
     * @param Closure(ARefusalInItsWords): TRefused      $refused
     * @param Closure(Obstacle): TMet                    $met
     *
     * @return TListed|TRefused|TMet
     */
    public function either(Closure $listed, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $listed($this->answer),
        };
    }
}
