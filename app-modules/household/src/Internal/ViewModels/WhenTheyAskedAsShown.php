<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** How long ago a member asked for something, for a template: the line as a catalogue key and the count it is chosen by, or nothing where the core did not say. */
final readonly class WhenTheyAskedAsShown
{
    public function __construct(public string $said, public int $count) {}

    /** Whether there is anything to draw. */
    public function saysWhen(): bool
    {
        return $this->said !== '';
    }
}
