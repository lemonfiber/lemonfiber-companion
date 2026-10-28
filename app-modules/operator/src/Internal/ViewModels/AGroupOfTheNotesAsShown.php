<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One group of a release's notes, in the stack's own words: its title, and each change under it. */
final readonly class AGroupOfTheNotesAsShown
{
    /** @param list<string> $changes */
    public function __construct(public string $title, public array $changes) {}
}
