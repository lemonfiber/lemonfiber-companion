<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where switching the active forms has got to, flattened for a template. */
final readonly class TheSwitchTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands     $stands       where the work stands, and whether the stack could be asked at all
     * @param string                $notAskable the catalogue key saying why nothing was sent, blank where it was
     * @param ARefusalAsShown|null  $refused    the stack's refusal, where it refused
     * @param TheSwitchAsShown|null $change     what the run reported, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public string $notAskable,
        public ?ARefusalAsShown $refused,
        public ?TheSwitchAsShown $change,
    ) {}
}
