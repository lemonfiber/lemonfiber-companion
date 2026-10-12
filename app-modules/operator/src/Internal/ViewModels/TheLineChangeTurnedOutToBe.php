<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where changing the line has got to, flattened for a template. */
final readonly class TheLineChangeTurnedOutToBe
{
    /**
     * @param HowTheReadingWent         $went       whether the stack answered, or what stood in the way
     * @param bool                      $isWorking  whether the stack is still at it
     * @param bool                      $hasEnded   whether the stack has no outcome for it any more
     * @param string                    $notAskable the catalogue key saying why what was typed was not sent, blank where it was
     * @param ARefusalAsShown|null      $refused    the stack's refusal, where it refused
     * @param TheLineChangeAsShown|null $change     what the run reported, where it finished
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public string $notAskable,
        public ?ARefusalAsShown $refused,
        public ?TheLineChangeAsShown $change,
    ) {}
}
