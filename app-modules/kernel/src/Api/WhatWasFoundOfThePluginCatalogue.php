<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking a stack for its plugin catalogue came away with: the stack's answer, or what stood in the way.
 *
 * A value rather than a raise for {@see WhatWasRecorded}'s reason. A stack
 * that could not be asked is never a catalogue that offers nothing.
 */
final readonly class WhatWasFoundOfThePluginCatalogue
{
    private function __construct(private ThePluginCatalogue|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function found(ThePluginCatalogue $catalogue): self
    {
        return new self($catalogue);
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
     * @param Closure(ThePluginCatalogue): TFound $found
     * @param Closure(Obstacle): TMet       $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $found($this->answer);
    }
}
