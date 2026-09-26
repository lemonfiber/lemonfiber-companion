<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One invitation, as the stack answered it: made, or only described.
 *
 * A rehearsal says the whole answer without writing any of it — the name is
 * the one asked for, the address is the stack's, and what has run out has just
 * been read — so the one thing separating it from an account that exists is
 * which constructor made it. Each is its own named constructor rather than a
 * flag, so a reader never has to remember which way round a boolean was.
 *
 * What it grants is added rather than defaulted: an offer that set nothing
 * about access writes nothing, which is not the same as one that set no
 * restrictions, and {@see self::granted()} says which arm it is on.
 */
final readonly class AnInvitation
{
    private function __construct(
        private AnInvitationToHand $toHand,
        private WhereTheInvitationStands $standing,
        private WhetherTheyCanAsk $linked,
        private WhoWasTakenBack $withdrawn,
        private bool $rehearsed,
        private ?WhatWasGranted $granted = null,
    ) {}

    /** What inviting them would come to, with nothing made and nothing taken back. */
    public static function rehearsed(
        AnInvitationToHand $toHand,
        WhereTheInvitationStands $standing,
        WhetherTheyCanAsk $linked,
        WhoWasTakenBack $withdrawn,
    ): self {
        return new self($toHand, $standing, $linked, $withdrawn, rehearsed: true);
    }

    /** What inviting them came to, carried out. */
    public static function carriedOut(
        AnInvitationToHand $toHand,
        WhereTheInvitationStands $standing,
        WhetherTheyCanAsk $linked,
        WhoWasTakenBack $withdrawn,
    ): self {
        return new self($toHand, $standing, $linked, $withdrawn, rehearsed: false);
    }

    /** The same answer, with what it wrote, or would write, on the account. */
    public function granting(WhatWasGranted $granted): self
    {
        return new self($this->toHand, $this->standing, $this->linked, $this->withdrawn, $this->rehearsed, $granted);
    }

    /** The name, the address and how long it stands. */
    public function toHand(): AnInvitationToHand
    {
        return $this->toHand;
    }

    /** What the stack found where this was going. */
    public function standing(): WhereTheInvitationStands
    {
        return $this->standing;
    }

    /** Whether the request service knows about them yet. */
    public function linked(): WhetherTheyCanAsk
    {
        return $this->linked;
    }

    /** Invitations nobody claimed in time, taken back on the way past, or that would be. */
    public function withdrawn(): WhoWasTakenBack
    {
        return $this->withdrawn;
    }

    /** Whether this only described the invitation, making nothing. */
    public function wasRehearsed(): bool
    {
        return $this->rehearsed;
    }

    /**
     * What it wrote on the account, or that it wrote nothing about access at all.
     *
     * @template TGranted of object
     * @template TNothing of object
     *
     * @param Closure(WhatWasGranted): TGranted $these
     * @param Closure(): TNothing               $nothing
     *
     * @return TGranted|TNothing
     */
    public function granted(Closure $these, Closure $nothing): object
    {
        return $this->granted instanceof WhatWasGranted ? $these($this->granted) : $nothing();
    }
}
