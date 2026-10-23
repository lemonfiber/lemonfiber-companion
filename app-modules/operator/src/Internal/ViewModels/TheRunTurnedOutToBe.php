<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where running a check that disturbs has got to, flattened for a template. */
final readonly class TheRunTurnedOutToBe
{
    /**
     * @param HowTheReadingWent    $went      whether the stack answered, or what stood in the way
     * @param bool                 $isWorking whether the stack is still running it
     * @param bool                 $hasEnded  whether the stack has no outcome for it any more
     * @param ARefusalAsShown|null $refused   the stack's refusal, where it refused
     * @param bool                 $answered  whether the run finished and reported
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public ?ARefusalAsShown $refused,
        public bool $answered,
    ) {}
}
