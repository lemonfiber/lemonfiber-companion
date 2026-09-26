<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking a stack to rehearse putting a copy back came away with.
 *
 * The listing, or what stood in the way. A stack that refused to list a copy
 * — one written by a newer lemonfiber, one it does not manage, one that is
 * corrupt — comes back through the second arm, and nothing can be agreed to
 * off it.
 */
final readonly class WhatTheRestoreRehearsalFound
{
    private function __construct(private WhatPuttingItBackWouldDo|Obstacle $answer) {}

    /** The stack listed the copy, and this is what putting it back would do. */
    public static function listed(WhatPuttingItBackWouldDo $listing): self
    {
        return new self($listing);
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
     * @template TMet of object
     *
     * @param Closure(WhatPuttingItBackWouldDo): TListed $listed
     * @param Closure(Obstacle): TMet                    $met
     *
     * @return TListed|TMet
     */
    public function either(Closure $listed, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $listed($this->answer);
    }
}
