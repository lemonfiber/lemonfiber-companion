<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One line of a capability set: an ability, and what the stack says about it.
 *
 * A pair rather than an array entry, which is `D1`: `array<string, Availability>`
 * in a public signature is a shape the analyser cannot check and a reader has to
 * take on trust — and the two halves of this pair are exactly the kind that get
 * silently transposed, because one of them is a string and the other is an enum
 * whose backing value is also a string.
 */
final readonly class Declared
{
    private function __construct(
        private Ability $ability,
        private Availability $availability,
    ) {}

    public static function of(Ability $ability, Availability $availability): self
    {
        return new self($ability, $availability);
    }

    public function ability(): Ability
    {
        return $this->ability;
    }

    public function availability(): Availability
    {
        return $this->availability;
    }
}
