<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** A yes the operator gave: the handle the stack took it on under, or that the stack did not take it on. */
final readonly class TheYesGiven
{
    /** @param ?string $handle what the stack answered the yes with, where it took it on; never shown and never kept past the screen */
    private function __construct(public ?string $handle) {}

    public static function takenOnAs(string $handle): self
    {
        return new self($handle);
    }

    public static function notTakenOn(): self
    {
        return new self(null);
    }
}
