<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where a guard the operator started stands.
 *
 * Six arms, because a guard ends in four ways and each is a different
 * afternoon. It saw the data location go and says what it did about it. It
 * never started, and the stack says why in its own words. It was ended without
 * seeing anything — released, or let go because nothing asked about it. Or the
 * stack no longer knows it, because the stack restarted and nothing it was
 * running survived that. The other two are the guard still guarding and a
 * stack that could not be asked.
 *
 * **Ended and unknown are kept apart.** Every other job here reads a name the
 * stack no longer knows as ended, since the work may well have finished. A
 * guard has no finish to have reached: one the stack forgot is one that is
 * not guarding, for a reason that is not the operator's doing.
 */
final readonly class HowTheGuardIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?WhatTheGuardSaw $saw = null,
        private string $refused = '',
        private ?Obstacle $met = null,
        private bool $guarding = false,
        private bool $forgotten = false,
    ) {}

    /** The stack is still guarding. */
    public static function stillGuarding(): self
    {
        return new self(guarding: true);
    }

    /** It saw the data location go, and this is what it did about it. */
    public static function sawItGo(WhatTheGuardSaw $saw): self
    {
        return new self(saw: $saw);
    }

    /** It never started, and the stack said why in these words. */
    public static function refused(string $said): self
    {
        return new self(refused: $said);
    }

    /** It ended without seeing anything: released, or let go because nothing asked about it. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack no longer knows it. */
    public static function unknown(): self
    {
        return new self(forgotten: true);
    }

    /** The stack could not be asked, and this is what was met. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Being unable to reach the stack outranks anything believed about the guard.
     *
     * @template T of object
     *
     * @param Closure(): T                $guarding
     * @param Closure(WhatTheGuardSaw): T $sawItGo
     * @param Closure(string): T          $refused
     * @param Closure(): T                $ended
     * @param Closure(): T                $unknown
     * @param Closure(Obstacle): T        $met
     *
     * @return T
     */
    public function either(Closure $guarding, Closure $sawItGo, Closure $refused, Closure $ended, Closure $unknown, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->guarding => $guarding(),
            $this->saw instanceof WhatTheGuardSaw => $sawItGo($this->saw),
            $this->refused !== '' => $refused($this->refused),
            $this->forgotten => $unknown(),
            default => $ended(),
        };
    }
}
