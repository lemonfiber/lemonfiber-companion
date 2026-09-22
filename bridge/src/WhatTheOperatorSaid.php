<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * What the operator has said about a permission, and what may be done about it.
 *
 * The PHP face of the same three words `WhatTheOperatorSaid.kt` and
 * `WhatTheOperatorSaid.swift` answer with. Three rather than two, and the third
 * is the point: *nobody has been asked* and *somebody said no* are the same
 * answer to "may I do this" and opposite answers to "may I ask" — an
 * application that cannot tell them apart either prompts somebody who already
 * refused, every time they open a screen, or never asks anybody at all.
 *
 * A backed enum because the value is what came off the wire. `tryFrom()` rather
 * than `from()` at the boundary: a word this does not know is a bridge that
 * grew a case since this was written, and that is a thing to read as "nobody
 * has been asked" rather than to raise on.
 */
enum WhatTheOperatorSaid: string
{
    /** The thing the permission guards would work right now. */
    case Granted = 'granted';

    /** It would not, and nothing may ask again. */
    case Denied = 'denied';

    /** Nobody has been asked; the point of first use is still ahead. */
    case NotDetermined = 'not_determined';

    /**
     * What the bridge said, or that it said nothing this type recognises.
     *
     * A bridge with no device behind it answers nothing at all, which is every
     * machine that is not a handset. Both that and an unrecognised word are
     * read as {@see self::NotDetermined}, which is the safe answer in both
     * directions: nothing is shown, and no refusal is recorded that nobody
     * made.
     *
     * **Two branches rather than `tryFrom($said ?? '')`.** Routing *nothing
     * said* through the empty string makes it the same path as a word this
     * type does not know, and the two are not the same thing — one is a
     * machine with no device behind it, the other is a bridge that grew a case
     * since this was written. Nothing could tell them apart either: the empty
     * string is not a case here, so every unrecognised word already lands where
     * it lands and swapping one for another changes nothing anybody can
     * observe. That is a line no test can hold, which is what the mutation
     * floor said about it. `WhatTheBridgeSaidTest` drives both.
     */
    public static function orNothingSaid(?string $said): self
    {
        if ($said === null) {
            return self::NotDetermined;
        }

        return self::tryFrom($said) ?? self::NotDetermined;
    }

    /**
     * Whether raising the prompt now could change the answer.
     *
     * Deliberately not the negation of {@see self::mayProceed()}. A grant and a
     * refusal are both reasons not to ask and opposite answers to whether
     * anything may happen, so a caller reading one for the other is an
     * application that prompts on every screen or never prompts at all.
     */
    public function mayAsk(): bool
    {
        return $this === self::NotDetermined;
    }

    /** Whether the thing the permission guards may happen now. */
    public function mayProceed(): bool
    {
        return $this === self::Granted;
    }
}
