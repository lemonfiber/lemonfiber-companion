<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * An invitation was agreed to against something that was not its offer.
 *
 * A developer reads it: the screen only offers the yes beneath a rehearsal it
 * was shown, so reaching this is a caller that skipped the offer.
 */
final class InvitationWasNotOffered extends LogicException
{
    /** The answer agreed against had already made the account. */
    public static function becauseItWasCarriedOut(): self
    {
        return new self('An invitation was agreed to against an answer that had already been carried out, which is not an offer anybody was shown.');
    }

    /** The rehearsal found them already in the household, so there was nothing to agree to. */
    public static function becauseTheyHaveJoined(): self
    {
        return new self('An invitation was agreed to for somebody the rehearsal found already in the household, which makes nothing and sends them a link to an account they use.');
    }
}
