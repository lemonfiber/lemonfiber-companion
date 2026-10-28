<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One line for a template: a catalogue key and what fills it. */
final readonly class ALineAsShown
{
    /** @param array<string, int|string> $filling */
    public function __construct(public string $key, public array $filling) {}
}
