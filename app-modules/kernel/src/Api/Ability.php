<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something a stack can be asked to do, named the way the API names it.
 *
 * A value object over the wire's string rather than an enum, and this is the one
 * place in the kernel where that is the right way round. `D4` asks for an enum
 * wherever the set is closed — and this set is declared by the stack, at
 * runtime, by a stack that may be newer than this app. An enum would be a
 * closed list of what this build had heard of, and a capability it had not heard
 * of would arrive as a `tryFrom` returning null, which reads as "the stack does
 * not have it" and means "this app does not recognise it". Those are opposite
 * answers, and they are separated by name.
 *
 * **What is closed is the set of actions the app offers**, because a screen
 * exists or it does not. That set lives where the screens are and is compared
 * against this by name — which is why this carries the name rather than
 * interpreting it.
 */
final readonly class Ability
{
    private function __construct(private string $ability) {}

    public static function of(string $ability): self
    {
        $trimmed = trim($ability);

        if ($trimmed === '') {
            throw AbilityIsUnnamed::inACapabilitySet();
        }

        return new self($trimmed);
    }

    /** The name, as the API declared it. */
    public function named(): string
    {
        return $this->ability;
    }

    public function is(self $other): bool
    {
        return $this->ability === $other->ability;
    }
}
