<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where changing a setting has got to, flattened for a template. */
final readonly class TheSettingChangeTurnedOutToBe
{
    /**
     * @param HowTheReadingWent            $went      whether the stack answered, or what stood in the way
     * @param bool                         $isWorking whether the stack is still at it
     * @param bool                         $hasEnded  whether the stack has no outcome for it any more
     * @param ARefusalAsShown|null         $refused   the stack's refusal, where it refused
     * @param TheSettingChangeAsShown|null $change    what the run reported, where it finished
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public ?ARefusalAsShown $refused,
        public ?TheSettingChangeAsShown $change,
    ) {}
}
