<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One thing an operator may do about a setup already here, flattened for a template. */
final readonly class AModeAsShown
{
    /**
     * @param string $mode         the word an operator types for it
     * @param string $what         what choosing it would come to, in the stack's words
     * @param string $disturbsSaid the catalogue key for whether it disturbs what is running
     * @param bool   $preselected  whether it is offered already chosen
     */
    public function __construct(
        public string $mode,
        public string $what,
        public string $disturbsSaid,
        public bool $preselected,
    ) {}
}
