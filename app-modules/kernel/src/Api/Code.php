<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A stable identifier for a kind of problem.
 *
 * Stability is the whole point: an operator who searches for a code should find
 * the same answer a year later. The server declares them beside the code that
 * raises them and never recycles one, and this side only carries them.
 *
 * A type rather than a string because a code and a summary are both strings,
 * and nothing stops one being passed where the other belongs — the mistake
 * compiles, ships, and shows the operator `STACK-7` where a sentence should be.
 */
final readonly class Code
{
    private function __construct(private string $code) {}

    /**
     * The one place a string becomes a code.
     *
     * Empty is refused rather than carried. A code is what a screen shows
     * beside a refusal and what somebody searches for later, and an empty one
     * is a blank space where an identifier should be — which reads as a
     * rendering fault rather than as a server that sent nothing.
     */
    public static function of(string $code): self
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            throw CodeIsBlank::inAProblem();
        }

        return new self($trimmed);
    }

    public function shown(): string
    {
        return $this->code;
    }

    public function is(self $other): bool
    {
        return $this->code === $other->code;
    }
}
