<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The forms a stack declares, or what stopped them being listed, for a template.
 *
 * `$names` is empty both where the stack declares no form and where the list
 * could not be read. `$went` tells the two apart, and a screen asks it before
 * it reads the names.
 */
final readonly class TheFormsAsFound
{
    /** @param list<string> $names each form by name, in the stack's order */
    public function __construct(
        public HowTheReadingWent $went,
        public array $names,
    ) {}
}
