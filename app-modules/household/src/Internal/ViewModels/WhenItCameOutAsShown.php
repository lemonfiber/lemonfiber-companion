<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** When a title came out, for a template: the line as a catalogue key, its day and year, and its month's key. */
final readonly class WhenItCameOutAsShown
{
    /** @param array<string, int> $filling */
    public function __construct(public string $key, public array $filling, public string $month) {}
}
