<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where changing a setting has got to, flattened for a template. */
final readonly class TheSettingChangeTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands            $stands      where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null         $refused   the stack's refusal, where it refused
     * @param TheSettingChangeAsShown|null $change    what the run reported, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public ?TheSettingChangeAsShown $change,
    ) {}
}
