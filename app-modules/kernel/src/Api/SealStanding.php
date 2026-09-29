<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where the phone stands on sealing what it keeps, asked before anything is kept.
 *
 * Three answers, and each is a different thing for the owner of kept data to
 * do: carry on, clear what it kept and say so once, or keep nothing at all and
 * say why.
 */
enum SealStanding
{
    /** The key is there and reads. What was sealed before opens. */
    case Held;

    /**
     * The key was missing or unreadable, and a new one was made now.
     *
     * Anything sealed before is unreadable under it, so whatever an owner kept
     * is to be cleared rather than tried and thrown away row by row.
     */
    case MadeAfresh;

    /**
     * Nothing can be sealed: there is no secure storage, or the store holding
     * the key would not open.
     *
     * Which of the two is {@see WhyNothingIsSealed}'s to say, where
     * {@see Sealed::seal()} refuses. Either way an owner keeps nothing, and
     * deletes nothing either: a store that would not open may open next time,
     * and what it sealed under the key it holds is still readable then.
     */
    case Unavailable;

    /**
     * Where two keys held together stand: the worse of the two.
     *
     * A key that cannot be had is worse than one made now, and one made now
     * is worse than one that was there, because each is a larger thing for an
     * owner to do about it.
     */
    public function beside(self $other): self
    {
        if ($this === self::Unavailable || $other === self::Unavailable) {
            return self::Unavailable;
        }

        if ($this === self::MadeAfresh || $other === self::MadeAfresh) {
            return self::MadeAfresh;
        }

        return self::Held;
    }
}
