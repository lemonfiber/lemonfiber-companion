<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack would put right, or the reason nobody could ask it.
 *
 * What the port that asks a stack what it would put right answers with, in the
 * shape {@see WhatCameBack} settled on for the same question one screen over.
 * A value
 * rather than an exception, because a stack that is asleep, one on another
 * network and one whose session has ended are ordinary states of the world
 * (`C1`), and an {@see Obstacle} rather than a vocabulary of its own, because
 * the operator meets the same six situations whether they were signing in,
 * asking after the machine, or asking what it could fix.
 *
 * **The offer is carried beside the repairs, and that is the point of the
 * type.** `N2-R6` says a confirmation made against one reading must not be
 * carried out if the reading has changed, and the offer is what names the
 * reading: the engine hands back a word identifying this listing, and a
 * confirmation that quotes it is a confirmation the engine can check. A screen
 * holding repairs and no offer could only say yes in the abstract — which the
 * engine accepts, as standing consent, and which is exactly what `N2-R5`
 * forbids this surface from sending.
 *
 * **No repairs is not an obstacle.** Most runs offer none, because most
 * findings are things the operator has to go and do. That arrives as an empty
 * {@see Repairs} under a perfectly good offer, and a screen reads it as
 * *nothing to offer here* rather than as a failure to ask — the same
 * distinction {@see Report} draws between a healthy stack and an unread one.
 */
final readonly class WhatIsOnOffer
{
    private function __construct(private Offer|Obstacle $answer) {}

    /** The stack answered, and this is what it would put right. */
    public static function offer(Offer $offer): self
    {
        return new self($offer);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * A union field rather than two nullables, so there is no fourth state to
     * write an unreachable branch for — the shape {@see Admitted} settled on
     * after the mutation gate found the branch nothing could kill.
     *
     * @template TOffered of object
     * @template TMet of object
     *
     * @param Closure(Offer): TOffered $offered
     * @param Closure(Obstacle): TMet  $met
     *
     * @return TOffered|TMet
     */
    public function either(Closure $offered, Closure $met): object
    {
        // Read off the offer, the way `WhatCameBack` does: the arm the type is
        // written around is read first, and a fall-through is how a branch
        // becomes the one nobody tested.
        return $this->answer instanceof Offer
            ? $offered($this->answer)
            : $met($this->answer);
    }
}
