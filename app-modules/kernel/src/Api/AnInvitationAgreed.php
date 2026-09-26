<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * An invitation the operator agreed to, having been shown what it would come to.
 *
 * The only way to make one is against a rehearsal that answered the same
 * request, which is what makes the thing sent the thing that was shown: the
 * arguments carried here are the ones the offer was asked with, and nothing an
 * operator typed afterwards.
 *
 * A rehearsal that found them already in the household is refused. There is
 * nothing to agree to — the stack makes nothing for somebody who has joined —
 * and sending it anyway would put an invitation in front of somebody who has
 * been watching all along.
 *
 * The names are not compared. The stack answers under the name an account is
 * already held by where it finds one, which may be spelled otherwise than the
 * name that was asked, and the request sent is the one asked either way.
 */
final readonly class AnInvitationAgreed
{
    private function __construct(private AnInvitationAskedFor $asked) {}

    /** The same request again, now agreed to; an answer that was not its rehearsal is refused. */
    public static function after(AnInvitationAskedFor $asked, AnInvitation $offered): self
    {
        if (! $offered->wasRehearsed()) {
            throw InvitationWasNotOffered::becauseItWasCarriedOut();
        }

        if (! $offered->standing()->leavesSomethingToHandOver()) {
            throw InvitationWasNotOffered::becauseTheyHaveJoined();
        }

        return new self($asked);
    }

    /** What the offer was asked with, which is what is sent. */
    public function asked(): AnInvitationAskedFor
    {
        return $this->asked;
    }
}
