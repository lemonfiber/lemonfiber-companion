<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where asking to move in has got to, flattened for the template.
 *
 * One model for every state, each saying only its own: nothing asked yet, the
 * stack still working, a move answered, the request turned down with the
 * stack's reason, or the work ended with no outcome.
 */
final readonly class TheMoveTurnedOutToBe
{
    /**
     * @param HowTheReadingWent $went      whether the stack was reached, and what stood in the way where it was not
     * @param string            $mode      the word of the mode asked about, or empty where nothing has been
     * @param bool              $isWorking whether the stack is still working it out
     * @param bool              $hasEnded  whether the stack has no outcome for it any more
     * @param string            $refusal   the stack's words turning the request down, or empty
     * @param AMoveAsShown|null $move      where the move stands, or nothing where none has come back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $mode,
        public bool $isWorking,
        public bool $hasEnded,
        public string $refusal,
        public ?AMoveAsShown $move,
    ) {}
}
