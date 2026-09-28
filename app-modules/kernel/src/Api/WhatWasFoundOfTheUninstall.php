<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What reading one removal came away with: the reading, or what stood in the way.
 *
 * A value rather than a raise for {@see WhatWasRecorded}'s reason. A reading
 * that could not be read is never a machine with nothing on it.
 */
final readonly class WhatWasFoundOfTheUninstall
{
    private function __construct(private AnUninstall|Obstacle $answer) {}

    /** The stack read it, and this is the reading. */
    public static function found(AnUninstall $uninstall): self
    {
        return new self($uninstall);
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
     * @param Closure(AnUninstall): TFound $found
     * @param Closure(Obstacle): TMet      $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $found($this->answer);
    }
}
