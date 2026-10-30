<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where changing what the household may ask for has got to, flattened for a template. */
final readonly class TheAllowanceChangeTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands              $stands       where the work stands, and whether the stack could be asked at all
     * @param ?string                         $notAskable the catalogue key saying why what was chosen was not sent, where it was not
     * @param ARefusalAsShown|null           $refused    the stack's refusal, where it refused
     * @param TheAllowanceChangeAsShown|null $change     what the run reported, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?string $notAskable = null,
        public ?ARefusalAsShown $refused = null,
        public ?TheAllowanceChangeAsShown $change = null,
    ) {}
}
