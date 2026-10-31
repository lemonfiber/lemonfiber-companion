<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where replacing the internal certificate has got to, flattened for a template. */
final readonly class TheReplacementTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands       $stands      where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null     $refused     the stack's refusal, where it refused
     * @param AReplacementAsShown|null $replacement what the stack read or did, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public ?AReplacementAsShown $replacement,
    ) {}
}
