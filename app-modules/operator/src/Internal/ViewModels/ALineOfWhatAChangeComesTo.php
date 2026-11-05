<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One line of what a change comes to: a catalogue key, what it takes, and whether it explains the line above it.
 */
final readonly class ALineOfWhatAChangeComesTo
{
    /** @param array<string, int|string> $with */
    public function __construct(
        public string $said,
        public array $with,
        public bool $aside,
    ) {}
}
