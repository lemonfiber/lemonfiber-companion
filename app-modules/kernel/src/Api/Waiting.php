<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a household member's request stands.
 *
 * The words are the stack's own, from the `household` envelope, so a value this
 * app cannot read is refused where the payload is read rather than guessed at.
 *
 * **Only one of these wants a decision.** The rule is about requests awaiting
 * one, and a screen offering to approve something that has already arrived
 * would be offering to do nothing — which is `Standing`'s documented failure
 * one feature over: a screen that confuses somebody offers to do something it
 * cannot do.
 */
enum Waiting: string
{
    /** Nobody has decided yet, which is the one that matters. */
    case ForApproval = 'waiting-for-approval';

    /** The operator said no, and a reason reached them. */
    case Declined = 'declined';

    /** It was approved and the fetching did not work. */
    case Failed = 'failed';

    /** It is being fetched. */
    case Getting = 'getting';

    /** Some of it is here, which for a season is the ordinary middle. */
    case PartlyHere = 'partly-here';

    /** All of it is here. */
    case Here = 'here';

    /** It was here and is not any more. */
    case Gone = 'gone';

    /**
     * Whether this is a request the operator still has to answer.
     *
     * The line is drawn once, here, rather than at each screen that needs it:
     * a screen deciding for itself is how two screens come to disagree about
     * what is waiting, and the operator learns that one of them is lying —
     * which is the argument {@see Severity::demandsAttention()} makes.
     */
    public function wantsADecision(): bool
    {
        return $this === self::ForApproval;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is how every word in this app reaches the
     * catalogue — see {@see Conclusion::saidOnTheScreen()} for the argument.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('household.%s', $this->value);
    }
}
