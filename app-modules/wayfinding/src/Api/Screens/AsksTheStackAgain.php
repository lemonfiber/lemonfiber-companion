<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Modules\Kernel\Api\Stack;
use Modules\Wayfinding\Api\TheWayAround;

/**
 * The operator's *ask again*, which asks the stack what it offers as well.
 *
 * Apart from a screen's own `again()`, which its cadence calls too: what a
 * stack offers is held for a while rather than asked on every look, and the
 * person tapping *ask again* is saying they no longer trust what was said, so
 * that tap, and only that tap, lets go of it.
 *
 * @property-read TheWayAround $around
 */
trait AsksTheStackAgain
{
    /** Read again, by the screen's own rule for what that means. */
    abstract public function again(): void;

    /** The stack this screen is about. */
    abstract public function stack(): Stack;

    /** Ask again, of the stack's reading and of what the stack offers. */
    public function askAgain(): void
    {
        $this->around->askAgainOf($this->stack());
        $this->again();
    }
}
