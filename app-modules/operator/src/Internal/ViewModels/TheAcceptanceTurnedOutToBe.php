<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where accepting a warning has got to, flattened for a template. */
final readonly class TheAcceptanceTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands    $stands      where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null $refused   the stack's refusal, where it refused
     * @param ?bool                $accepted  whether the report the stack finished with shows the warning accepted, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused = null,
        public ?bool $accepted = null,
    ) {}
}
