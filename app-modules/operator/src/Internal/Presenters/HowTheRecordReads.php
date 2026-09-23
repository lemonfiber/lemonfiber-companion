<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Operator\Internal\ViewModels\AMomentOnTheRecord;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheRecordTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatOneRecordedChangeSays;

/**
 * What asking a stack for its record produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out, with the moment the ages are measured
 * against handed in (`B1`), so *this change was made two hours ago* is a
 * sentence a test states rather than a clock it arranges.
 */
final readonly class HowTheRecordReads
{
    /** The line for a change the stack's clock would not date. */
    public const string CLOCK_UNREADABLE = 'stacks.record.clock_unreadable';

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The empty fields are what the template branches on.
     */
    public function signedOut(): TheRecordTurnedOutToBe
    {
        return new TheRecordTurnedOutToBe(went: HowTheReadingWent::theSessionEnded(), horizon: '', moments: []);
    }

    /**
     * The stack answered, and this is its record.
     *
     * The horizon is carried whether or not anything is under it: an empty
     * record that did not say how far back it reaches would read as *nothing
     * has ever happened here*.
     */
    public function this(TheRecord $record, Instant $now): TheRecordTurnedOutToBe
    {
        return new TheRecordTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            horizon: $record->horizon(),
            moments: $this->moments($record, $now),
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): TheRecordTurnedOutToBe
    {
        return new TheRecordTurnedOutToBe(went: HowTheReadingWent::somethingStopped($why), horizon: '', moments: []);
    }

    /**
     * The record as moments, each holding every change made then.
     *
     * Consecutive changes made at one known moment share a moment, so the two
     * are drawn under one *when* rather than as one before the other. The
     * stack's order is kept within and between them — grouping is not
     * sorting. A change whose clock would not answer is always a moment of its
     * own: nobody knows it happened with anything.
     *
     * @return list<AMomentOnTheRecord>
     */
    private function moments(TheRecord $record, Instant $now): array
    {
        $moments = [];
        $rows = [];
        $when = null;

        foreach ($record as $change) {
            if ($when instanceof WhenItWasMade && ! $change->when()->isTheSameMomentAs($when)) {
                $moments[] = $this->moment($when, $rows, $now);
                $rows = [];
            }

            $when = $change->when();
            $rows[] = new HowARecordedChangeReads()->in($change);
        }

        if ($when instanceof WhenItWasMade) {
            $moments[] = $this->moment($when, $rows, $now);
        }

        return $moments;
    }

    /**
     * One moment, with when it was said as an age or as the clock not saying.
     *
     * @param list<WhatOneRecordedChangeSays> $rows
     */
    private function moment(WhenItWasMade $when, array $rows, Instant $now): AMomentOnTheRecord
    {
        return $when->either(
            at: static function (Instant $at) use ($rows, $now): AMomentOnTheRecord {
                $unit = HowLongAgo::since($at, $now);

                return new AMomentOnTheRecord($unit->saidOnTheScreen(), $unit->howManySince($at, $now), $rows);
            },
            unreadable: static fn(): AMomentOnTheRecord => new AMomentOnTheRecord(self::CLOCK_UNREADABLE, 0, $rows),
        );
    }
}
