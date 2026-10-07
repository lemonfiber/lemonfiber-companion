<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\MovingInBy;

/** One thing an operator may do about a setup already here, flattened for a template. */
final readonly class AModeAsShown
{
    /**
     * @param string $mode         the word an operator types for it
     * @param string $what         what choosing it would come to, in the stack's words
     * @param string $disturbsSaid the catalogue key for whether it disturbs what is running
     * @param bool   $preselected  whether it is offered already chosen
     * @param string          $askSaid the catalogue key for asking what it would come to, or empty where no act carries it out
     * @param MovingInBy|null $by      the act that carries it out, which is asked of the stack before it is offered, or none
     */
    public function __construct(
        public string $mode,
        public string $what,
        public string $disturbsSaid,
        public bool $preselected,
        public string $askSaid,
        public ?MovingInBy $by,
    ) {}
}
