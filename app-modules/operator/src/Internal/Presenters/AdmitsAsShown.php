<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

/** What a key admits, carried out of a fold as the catalogue key and what fills it. */
final readonly class AdmitsAsShown
{
    /** @param array<string, string> $filling */
    public function __construct(private string $said, private array $filling) {}

    /** @return array{string, array<string, string>} */
    public function pair(): array
    {
        return [$this->said, $this->filling];
    }
}
