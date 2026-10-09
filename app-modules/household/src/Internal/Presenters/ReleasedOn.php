<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

/** When something came out, as a catalogue key, its day and year, and its month's key, carried out of a fold. */
final readonly class ReleasedOn
{
    /** @param array<string, int> $filling */
    public function __construct(private string $key, private array $filling, private string $month) {}

    /** @return array{string, array<string, int>, string} */
    public function asDrawn(): array
    {
        return [$this->key, $this->filling, $this->month];
    }
}
