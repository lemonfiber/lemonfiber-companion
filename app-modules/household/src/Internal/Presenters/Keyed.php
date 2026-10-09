<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

/** A line, as a catalogue key and what fills it, carried out of a fold. */
final readonly class Keyed
{
    /** @param array<string, int|string> $filling */
    public function __construct(private string $key, private array $filling) {}

    /** @return array{string, array<string, int|string>} */
    public function asDrawn(): array
    {
        return [$this->key, $this->filling];
    }
}
