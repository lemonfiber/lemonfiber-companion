<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where a wiring run has got to, flattened for the template.
 *
 * One model for every state, each saying only its own: nothing asked yet, the
 * stack still working, a run answered, the request turned down with the
 * stack's reason, or the work ended with no outcome.
 */
final readonly class TheWiringTurnedOutToBe
{
    /**
     * @param HowTheReadingWent     $went      whether the stack was reached, and what stood in the way where it was not
     * @param bool                  $isWorking whether the stack is still carrying the run out
     * @param bool                  $hasEnded  whether the stack has no outcome for it any more
     * @param string                $refusal   the stack's words turning the request down, or empty
     * @param TheWiringAsShown|null $wiring    what the run came to, or nothing where none has come back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refusal,
        public ?TheWiringAsShown $wiring,
    ) {}
}
