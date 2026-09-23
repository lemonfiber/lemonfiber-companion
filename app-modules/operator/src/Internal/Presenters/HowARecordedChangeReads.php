<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\WhereItStopsShort;
use Modules\Operator\Internal\ViewModels\WhatOneRecordedChangeSays;
use Modules\Operator\Internal\WhatARecordedChangeSuggests;

/**
 * One change the stack recorded, as the row the record lists it on.
 *
 * Not {@see HowAChangeReads}, which is a setting being changed from this app.
 * This is a change the stack already made, read back.
 *
 * `F2` — the change is the only argument, so a row is read in a test by
 * stating one change and nothing else.
 */
final readonly class HowARecordedChangeReads
{
    /**
     * Fold one change into the fields a row needs.
     *
     * The empty strings are the *nowhere* arm rather than defaults: the values
     * one layer down refuse a blank reason and a blank suggestion, so an empty
     * here can only mean nothing stops the change short, or nothing is
     * suggested.
     */
    public function in(Change $change): WhatOneRecordedChangeSays
    {
        return $change->stopsShort(
            there: static fn(WhereItStopsShort $where): WhatOneRecordedChangeSays => new WhatOneRecordedChangeSays(
                did: $change->did(),
                operation: $change->operation(),
                target: $change->target(),
                reversalSaid: $change->reversal()->saidOnTheScreen(),
                alongside: $change->alongside(),
                because: $where->why(),
                instead: self::insteadOf($where),
            ),
            nowhere: static fn(): WhatOneRecordedChangeSays => new WhatOneRecordedChangeSays(
                did: $change->did(),
                operation: $change->operation(),
                target: $change->target(),
                reversalSaid: $change->reversal()->saidOnTheScreen(),
                alongside: $change->alongside(),
                because: '',
                instead: '',
            ),
        );
    }

    /** What to do instead, or empty where nothing is suggested. */
    private static function insteadOf(WhereItStopsShort $where): string
    {
        return $where->instead(
            said: static fn(string $what): WhatARecordedChangeSuggests => new WhatARecordedChangeSuggests($what),
            nothing: static fn(): WhatARecordedChangeSuggests => new WhatARecordedChangeSuggests(''),
        )->said;
    }
}
