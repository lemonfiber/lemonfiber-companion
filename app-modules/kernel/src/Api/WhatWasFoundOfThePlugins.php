<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking which plugins are installed came away with: the reading, or what stood in the way.
 *
 * A value rather than a raise for {@see WhatWasRecorded}'s reason. A record
 * that could not be read is never a stack with no plugins.
 */
final readonly class WhatWasFoundOfThePlugins
{
    private function __construct(private ThePlugins|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function found(ThePlugins $plugins): self
    {
        return new self($plugins);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TMet of object
     *
     * @param Closure(ThePlugins): TFound $found
     * @param Closure(Obstacle): TMet     $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $found($this->answer);
    }
}
