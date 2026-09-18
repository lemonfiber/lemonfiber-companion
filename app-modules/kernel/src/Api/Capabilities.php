<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;

/**
 * What one stack says it can do, and which stack said it.
 *
 * **The `StackId` is not decoration.** Where two configured
 * stacks differ in what they support, the app must not present one's
 * capabilities as another's — and a bare list of abilities has no way to refuse
 * that. A screen holding a capability set from the stack it was showing a minute
 * ago, now showing a different one, is the whole failure: the buttons are right
 * for a machine nobody is looking at. Carrying the id makes the mismatch a
 * question something can be asked rather than a mistake nobody can see.
 *
 * **Absence is not a case.** A capability the stack does not
 * have to be absent from the set rather than reported false, so
 * {@see self::of()} holds only what the stack declared and
 * {@see self::forAbility()} answers {@see WhatTheStackSays::nothing()} for
 * anything else — a case in a type of its own rather than a fourth
 * `Availability`, which would put "cannot" and "may not" side by side in one
 * list where a screen treats them the same because they are the same shape.
 */
final readonly class Capabilities
{
    /** @param array<string, Availability> $declared */
    private function __construct(
        private StackId $stack,
        private array $declared,
    ) {}

    /**
     * A capability set as one stack declared it.
     *
     * Variadic pairs rather than an array, which is `D1`: a map in a public
     * signature is a shape the analyser cannot check, and these two halves are
     * exactly the kind that get transposed — one is a string and the other is an
     * enum whose backing value is also a string.
     */
    public static function of(StackId $stack, Declared ...$declared): self
    {
        $byName = [];

        foreach ($declared as $line) {
            $byName[$line->ability()->named()] = $line->availability();
        }

        return new self($stack, $byName);
    }

    /** A stack that has declared nothing, which is not the same as a stack that can do nothing. */
    public static function undeclared(StackId $stack): self
    {
        return new self($stack, []);
    }

    /** Which stack said this. */
    public function stack(): StackId
    {
        return $this->stack;
    }

    /**
     * What this stack says about one ability, including having said nothing.
     *
     * Answered as a type rather than a nullable, which is `C2` and is also what
     * keeps the fourth answer from sitting beside the other three: a
     * stack that does not have a capability does not report it as false, and
     * absence read out of the same list as "not permitted" is how a screen ends
     * up saying one when it means the other.
     */
    public function forAbility(Ability $ability): WhatTheStackSays
    {
        $named = $ability->named();

        return array_key_exists($named, $this->declared)
            ? WhatTheStackSays::declared($this->declared[$named])
            : WhatTheStackSays::nothing();
    }

    /**
     * Whether a screen may offer this ability as an action on this stack.
     *
     * The question asked before an action is offered, answered in
     * one place so no screen works it out from a version number — which the same
     * requirement forbids by name.
     */
    public function offers(Ability $ability): bool
    {
        return $this->forAbility($ability)->offersAnAction();
    }

    /**
     * Whether this set is the one this stack declared.
     *
     * made structural: a screen that holds a stack and a capability set
     * can ask whether they belong together, and the answer does not depend on
     * anybody remembering to check.
     */
    public function describes(StackId $stack): bool
    {
        return $this->stack->is($stack);
    }
}
