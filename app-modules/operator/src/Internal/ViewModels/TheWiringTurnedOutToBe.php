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
     * @param WhereTheWorkStands     $stands      where the work stands, and whether the stack could be asked at all
     * @param ?string               $refusal   the stack's words turning the request down, where it did
     * @param TheWiringAsShown|null $wiring    what the run came to, or nothing where none has come back
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?string $refusal = null,
        public ?TheWiringAsShown $wiring = null,
    ) {}
}
