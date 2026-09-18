<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of an action the operator asked for.
 *
 * `ADR-0020` is the design, and the requirement is this: an action the app
 * could not deliver is **refused rather than retained**. The obvious kindness —
 * hold it and send it when the stack comes back — is the thing the ADR spends
 * its length rejecting, because an action queued on a phone is an action the
 * operator believes has happened, applied at a moment nobody chose, against a
 * stack whose state has moved on.
 *
 * So there are two arms and neither of them is "pending". That absence is the
 * requirement: retaining an undelivered action is forbidden, and so is replaying one
 * on reconnecting, **and presenting one as pending** — and a type with no arm
 * for it cannot present one.
 *
 * **The refusal names the stack and says nothing changed.** Both halves are
 * the other's, and both are carried here rather than left to a screen: an
 * operator who pressed a button and saw a red message needs to know which
 * machine it was about and whether to worry that it half-happened. "Nothing was
 * changed" is the sentence that makes the difference between an error and a
 * catastrophe, and it must not depend on which screen the refusal reached.
 */
final readonly class Attempted
{
    private function __construct(
        private StackId $on,
        private ?Problem $refusal,
    ) {}

    /** The stack received it and said what it did. */
    public static function delivered(StackId $on): self
    {
        return new self($on, null);
    }

    /**
     * It never reached the stack, so it did not happen.
     *
     * Takes the stack because the refusal has to name it, and the problem
     * because what went wrong is the server's or the network's to describe.
     */
    public static function refused(StackId $on, Problem $refusal): self
    {
        return new self($on, $refusal);
    }

    /**
     * Which stack this was about.
     *
     * Available on both arms, because a screen showing several stacks needs it
     * either way — and because a refusal that cannot name its stack is the
     * failure described.
     */
    public function on(): StackId
    {
        return $this->on;
    }

    /**
     * @template TDelivered of object
     * @template TRefused of object
     *
     * @param  Closure(StackId): TDelivered  $delivered
     * @param  Closure(Problem, StackId): TRefused  $refused
     * @return TDelivered|TRefused
     */
    public function either(Closure $delivered, Closure $refused): object
    {
        return $this->refusal instanceof Problem
            ? $refused($this->refusal, $this->on)
            : $delivered($this->on);
    }
}
