<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a held subscription to a stack's event stream had said when it was last asked.
 *
 * Five answers, for {@see WhatWasHeard}'s reason: a screen does something
 * different with each. Nothing arrived, and the subscription is open. Something
 * arrived that was not what the screen listens for, which proves the stack is
 * still there. What it listens for arrived, and this is the last of it since
 * the screen last asked. The subscription is closed, by the stack or by the
 * app. Or it could not be opened or read, and the obstacle says why.
 *
 * @template-covariant T of object
 */
final readonly class WhatAStreamSaid
{
    /** @param T|null $heard */
    private function __construct(
        private ?object $heard = null,
        private ?Obstacle $why = null,
        private bool $alive = false,
        private bool $closed = false,
    ) {}

    /** @return self<never> */
    public static function nothing(): self
    {
        /** @var self<never> */
        return new self();
    }

    /** @return self<never> */
    public static function aSignOfLife(): self
    {
        /** @var self<never> */
        return new self(alive: true);
    }

    /**
     * The last thing the screen listens for, carried as the stack said it.
     *
     * @template U of object
     *
     * @param U $heard
     *
     * @return self<U>
     */
    public static function said(object $heard): self
    {
        return new self(heard: $heard);
    }

    /** @return self<never> */
    public static function closed(): self
    {
        /** @var self<never> */
        return new self(closed: true);
    }

    /** @return self<never> */
    public static function met(Obstacle $why): self
    {
        /** @var self<never> */
        return new self(why: $why);
    }

    /**
     * Every arm required, so a screen cannot forget that a subscription ends.
     *
     * @template R of object
     *
     * @param Closure(): R         $nothing
     * @param Closure(): R         $alive
     * @param Closure(T): R        $said
     * @param Closure(): R         $closed
     * @param Closure(Obstacle): R $met
     *
     * @return R
     */
    public function either(Closure $nothing, Closure $alive, Closure $said, Closure $closed, Closure $met): object
    {
        return match (true) {
            $this->heard !== null => $said($this->heard),
            $this->why instanceof Obstacle => $met($this->why),
            $this->alive => $alive(),
            $this->closed => $closed(),
            default => $nothing(),
        };
    }
}
