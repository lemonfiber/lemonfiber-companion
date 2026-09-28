<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of asking a stack to put its configuration back, or what that would revert.
 *
 * Shaped as {@see HowTheBundleIsGoing} is. The stack can refuse, saying why
 * in its own words — a recorded choice it could not read, a file it could not
 * write — and that is an answer about the reset rather than a stack that could
 * not be reached, so it is carried as one.
 *
 * A job the stack no longer has an outcome for is its own arm. After a yes it
 * leaves the operator not knowing what is now in their files, which is said as
 * that rather than as a failure.
 */
final readonly class HowTheResetIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?TheReset $done = null,
        private string $refused = '',
        private ?WhatTheRefusalNamed $named = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still at it. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is what it would revert, or reverted. */
    public static function done(TheReset $reset): self
    {
        return new self(done: $reset);
    }

    /** The stack refused, said why, and named what it refused over, where it named anything. */
    public static function refused(string $said, WhatTheRefusalNamed $named): self
    {
        return new self(refused: $said, named: $named);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack could not be reached to ask, and this is what was met. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Being unable to reach the stack outranks anything believed about the job.
     *
     * @template T of object
     *
     * @param Closure(): T                            $stillRunning
     * @param Closure(TheReset): T                    $done
     * @param Closure(string, WhatTheRefusalNamed): T $refused
     * @param Closure(): T                            $ended
     * @param Closure(Obstacle): T                    $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $refused, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof TheReset => $done($this->done),
            $this->named instanceof WhatTheRefusalNamed => $refused($this->refused, $this->named),
            default => $ended(),
        };
    }
}
