<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what one member may watch.
 *
 * Reading only. What is on a member's shelf is decided by their entitlements,
 * their age limit and the libraries they reach, and none of that is this app's
 * to apply — so there is nothing here that asks for something, approves one, or
 * spends an allowance. Those are {@see Asking}'s, and keeping them off this
 * port is what stops a player growing a request flow of its own.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Owing} gives: each stack's session is separate and every connection is
 * pinned against that stack's fingerprint, so a port taking a client would let
 * a caller pair the two up wrongly.
 *
 * **It takes {@see Whose} because a shelf belongs to somebody.** There is no
 * whole-household reading: the list is read from the media server *as* that
 * account, so the age limit, the blocked kinds and the libraries it reaches are
 * applied before the answer is written — and one answer about a house would be
 * wrong for whoever it was not read as.
 *
 * **It does not say how many.** How long a shelf is is the core's answer like
 * everything else on it, and a figure named here would be this app deciding
 * what part of somebody's library is worth showing them.
 */
interface Watching
{
    /** What the core says this member may watch, or why it would not say. */
    public function theShelfOf(Stack $stack, Session $session, Whose $whose): WhatTheyMayWatch;

    /**
     * What the core says somebody invited with the household's defaults may
     * watch, or why it would not say.
     *
     * Nobody's shelf: the core reads it for no account, so asking it names no
     * member and the answer carries nothing of anybody's. It is what the
     * operator previews the member's side with.
     */
    public function theDefaultShelf(Stack $stack, Session $session): WhatTheyMayWatch;

    /**
     * One title on this member's shelf in full, or that it is absent, or why
     * the core would not say.
     *
     * Read as the member, so a title outside their limits is absent, the
     * same answer as one the household does not hold.
     */
    public function theTitle(Stack $stack, Session $session, Whose $whose, HoldingId $title): WhatTheTitleIs;

    /**
     * What this member was part-way through and how far, most recent first, or
     * why the core would not say.
     *
     * Read as the member, as the shelf is, and located as a title is.
     */
    public function partWayThrough(Stack $stack, Session $session, Whose $whose): WhatTheyArePartWayThrough;
}
