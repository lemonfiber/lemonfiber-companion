<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a repair that stopped part-way left on the machine.
 *
 * A type rather than a nullable string, which is `C2`: absence gets a name, and
 * *nothing was left* is a real answer rather than a missing one. It is also the
 * answer an operator most wants — a repair that stopped and left nothing can be
 * agreed to again without a thought, and one that left something cannot.
 *
 * **Only {@see WhatBecameOfIt::Stopped} carries one**, because it is the only
 * outcome where the machine got part-way. That is why this is read through a
 * closure rather than published: a screen holding the string has to decide what
 * to do when it is empty, and a screen handed both cases has already been told.
 */
final readonly class LeftBehind
{
    private function __construct(private string $what) {}

    /**
     * Something is on the machine that was not there before.
     *
     * A blank description is refused rather than carried, for {@see Remedy}'s
     * reason: it renders as a line of nothing under a heading saying something
     * was left, which is worse than the heading alone.
     */
    public static function of(string $what): self
    {
        $said = trim($what);

        if ($said === '') {
            throw RepairSaysNothing::whatItLeft();
        }

        return new self($said);
    }

    /** The repair stopped and left the machine as it found it. */
    public static function nothing(): self
    {
        return new self('');
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TSomething of object
     * @template TNothing of object
     *
     * @param Closure(string): TSomething $something
     * @param Closure(): TNothing         $nothing
     *
     * @return TSomething|TNothing
     */
    public function either(Closure $something, Closure $nothing): object
    {
        return $this->what === '' ? $nothing() : $something($this->what);
    }
}
