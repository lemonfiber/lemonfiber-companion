<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What one stack said about one ability — including having said nothing.
 *
 * Four answers are drawn and three of them are {@see Availability}. The fourth
 * is absence: a capability the stack does not have is not reported as false, it
 * is not reported. This is where that fourth answer lives, and it is a type
 * rather than a null — a null is checked at the honest call sites and skipped
 * at the one written in a hurry, and the skipped one shows a button for
 * something the stack cannot do.
 *
 * The same shape as {@see Reach} and {@see Reading}, and for the same reason:
 * a caller says what happens in both cases, so there is no point at which "the
 * stack did not say" exists as a value that can be treated as "no".
 *
 *     $says->either(
 *         declared: fn (Availability $availability): Row => $this->show($availability),
 *         saidNothing: fn (): Row => $this->omit(),
 *     );
 *
 * **Absence and "not permitted" are not neighbours.** That is the whole of
 * A stack that cannot do something and a credential that may not are
 * different sentences to an operator, and only one of them is about the stack.
 * Keeping absence in a different type from the three availabilities is what
 * stops a screen rendering them from one list.
 */
final readonly class WhatTheStackSays
{
    private function __construct(private ?Availability $availability) {}

    public static function declared(Availability $availability): self
    {
        return new self($availability);
    }

    /** The stack did not name this ability at all. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TDeclared of object
     * @template TSaidNothing of object
     *
     * @param Closure(Availability): TDeclared $declared
     * @param Closure(): TSaidNothing          $saidNothing
     *
     * @return TDeclared|TSaidNothing
     */
    public function either(Closure $declared, Closure $saidNothing): object
    {
        return $this->availability instanceof Availability
            ? $declared($this->availability)
            : $saidNothing();
    }

    /**
     * What a button for it says, absence included.
     *
     * The one question asked before an action is offered, answered without an
     * `either` because every answer has a sentence of its own: a stack that
     * named nothing is too old to have it, which is what absence means.
     */
    public function offered(): WhetherItIsOffered
    {
        return $this->availability instanceof Availability
            ? $this->availability->offered()
            : WhetherItIsOffered::NeedsANewerLemonfiber;
    }
}
