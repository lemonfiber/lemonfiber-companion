<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One request a member made, as the three things they read about it.
 *
 * The member's counterpart to the operator's own row type, and shorter than it by
 * everything an operator needs in order to decide: there is no requester here, no
 * size, and nothing about whether a decision is wanted. A member is not deciding.
 *
 * **Who asked is deliberately absent.** Every row a member sees is their own,
 * because the core narrowed the reading — so a name would be their own repeated
 * down a list, and a field for a requester is a field that could one day hold
 * somebody else's.
 *
 * **A state and never a stage**, which is the whole of what this must get right.
 * Where a request stands arrives as a catalogue key off the state that owns it,
 * and the machinery behind it — a queue position, a percentage, which service is
 * fetching, the library's own handle for the thing — is not carried, because none
 * of it means anything to somebody waiting for a film.
 */
final readonly class WhatOneOfTheirRequestsSays
{
    /**
     * @param string $title what they asked for, in the stack's words
     * @param string $standing where it stands, as a key in the member's words
     * @param string $reason why it was refused, or empty where it was not
     */
    public function __construct(
        public string $title,
        public string $standing,
        public string $reason,
    ) {}

    /**
     * Whether there is a reason to draw beneath the state.
     *
     * Asked rather than restated in the template, so *refused, and here is why*
     * is one rule in one place. An empty reason and a refusal without one are the
     * same to a screen: there is nothing to put on the second line.
     */
    public function wasRefused(): bool
    {
        return $this->reason !== '';
    }
}
