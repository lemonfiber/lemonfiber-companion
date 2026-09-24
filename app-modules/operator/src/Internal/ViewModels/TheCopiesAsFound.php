<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The copies a machine holds, or what stopped them being listed, for a template.
 *
 * `$names` is empty both where no copy has been taken and where the list
 * could not be read. `$went` tells the two apart, and the template asks it
 * before it reads the names.
 */
final readonly class TheCopiesAsFound
{
    /** @param list<string> $names each copy by the name it was written under, in the stack's order */
    public function __construct(
        public HowTheReadingWent $went,
        public array $names,
    ) {}
}
