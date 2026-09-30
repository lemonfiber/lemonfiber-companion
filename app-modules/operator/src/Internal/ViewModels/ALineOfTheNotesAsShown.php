<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One line of a release's notes, flattened for a template: a group's title, or one change under it.
 *
 * In the stack's own words either way; `heading` says which it is drawn as.
 */
final readonly class ALineOfTheNotesAsShown
{
    public function __construct(public string $said, public bool $heading) {}
}
