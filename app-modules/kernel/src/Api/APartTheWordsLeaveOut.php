<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A part of the stack the plain Compose words alone do not bring up as lemonfiber does, and why.
 */
final readonly class APartTheWordsLeaveOut
{
    private function __construct(private string $part, private string $because) {}

    /** This part, by its id, for this reason in the operator's words. */
    public static function because(string $part, string $because): self
    {
        return new self($part, $because);
    }

    /** The part, by its id. */
    public function part(): string
    {
        return $this->part;
    }

    /** Why, in the operator's words. */
    public function reason(): string
    {
        return $this->because;
    }
}
