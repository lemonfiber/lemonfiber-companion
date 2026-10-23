<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function array_values;

/**
 * The checks a run would run that disturb something, as the lines a template draws.
 */
final readonly class TheDisturbancesAsShown
{
    /** @param list<ADisturbanceAsShown> $lines */
    private function __construct(public array $lines) {}

    /** These, in the stack's order. */
    public static function of(ADisturbanceAsShown ...$lines): self
    {
        return new self(array_values($lines));
    }
}
