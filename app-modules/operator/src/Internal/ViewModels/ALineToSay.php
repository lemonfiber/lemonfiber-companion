<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One line for a template: a catalogue key and what fills it. */
final readonly class ALineToSay
{
    /** @param array<string, string> $filling */
    public function __construct(public string $key, public array $filling) {}
}
