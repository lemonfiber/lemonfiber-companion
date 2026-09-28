<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The changes made at one moment, and when that was.
 *
 * A group rather than a timestamp on every row, because two changes made at
 * one instant must not be drawn as one coming before the other — listed with
 * a time each, the one above reads as the later. Drawn under a single *when*,
 * they read as what they are.
 *
 * **When is a catalogue key and a count**, the shape
 * {@see \Modules\Operator\Internal\Presenters\AgoAsShown} carries for an age: the template hands both to
 * `trans_choice`, so *a minute ago* and *two minutes ago* are the catalogue's
 * sentences rather than this file's. An unreadable clock is its own key with a
 * count of nothing, so it is a sentence too, and never a date.
 */
final readonly class AMomentOnTheRecord
{
    /**
     * @param string                          $whenSaid  the key for when, or for the clock not saying
     * @param int                             $whenCount how many of that unit, where there is a unit
     * @param list<WhatOneRecordedChangeSays> $changes   every change made then, in the stack's order
     * @param string                          $stamp     the stamp the stack keeps what was done then under, which is how putting it back is asked for
     * @param string                          $leadsWith what the first change made then did, which tells one moment's way back from another's to somebody being read to
     */
    public function __construct(
        public string $whenSaid,
        public int $whenCount,
        public array $changes,
        public string $stamp,
        public string $leadsWith,
    ) {}
}
