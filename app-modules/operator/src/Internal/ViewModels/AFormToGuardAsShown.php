<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One form a guard can be asked for, flattened for a template.
 */
final readonly class AFormToGuardAsShown
{
    /**
     * @param string $name    the form, as the stack names it
     * @param bool   $isNamed whether it is named for the guard
     */
    public function __construct(
        public string $name,
        public bool $isNamed,
    ) {}
}
