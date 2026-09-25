<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a copy came to, against what a copy is reckoned to manage in a minute.
 *
 * The stack measures the bytes before it writes anything and says whether
 * that is inside the budget. A copy past it is slow because of what is kept,
 * not because anything is wrong, and that is what an operator watching one
 * needs told apart. The stack's own verdict is carried rather than worked out
 * here from the two figures.
 */
final readonly class HowACopyPaced
{
    private function __construct(
        private int $moved,
        private int $budget,
        private bool $brisk,
    ) {}

    /** The bytes it came to, the bytes it may come to, and whether it is inside them. */
    public static function measured(int $moved, int $budget, bool $brisk): self
    {
        if ($moved < 0) {
            throw KeepingSaysNothing::below('moved', $moved);
        }

        if ($budget < 0) {
            throw KeepingSaysNothing::below('budget', $budget);
        }

        return new self($moved, $budget, $brisk);
    }

    /** The bytes the copy came to. */
    public function moved(): int
    {
        return $this->moved;
    }

    /** The bytes a copy can come to and still be expected to finish in a minute. */
    public function budget(): int
    {
        return $this->budget;
    }

    /** Whether the stack said this copy is inside that. */
    public function isBrisk(): bool
    {
        return $this->brisk;
    }
}
