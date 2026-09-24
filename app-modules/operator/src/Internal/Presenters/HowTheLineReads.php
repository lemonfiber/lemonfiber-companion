<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowFast;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\HowTheLineTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheCapAsShown;
use Modules\Operator\Internal\ViewModels\TheLineAsMeasured;

/**
 * What asking a stack how it shares its line produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out, with the moment the measurement's age is
 * counted from handed in (`B1`). The line's speed becomes a figure and a unit
 * through {@see HowFast}, in bits a second, which is what a line is sold in and
 * what the stack's own sentences on the same screen say. The monthly allowance
 * is an amount rather than a speed, and goes through {@see HowBig} like any
 * other size.
 */
final readonly class HowTheLineReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): HowTheLineTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is how its line is shared. */
    public function this(HowTheLineIsShared $line, Instant $now): HowTheLineTurnedOutToBe
    {
        $measured = $line->capacity(
            measured: static fn(WhatTheLineCarries $carries): TheLineAsMeasured => self::measured($carries, $now),
            unmeasured: static fn(): AsText => AsText::nothing(),
        );
        $cap = $line->cap(
            capped: static fn(AMonthlyCap $cap): TheCapAsShown => self::cap($cap),
            uncapped: static fn(): AsText => AsText::nothing(),
        );

        return new HowTheLineTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            standsSaid: $line->stands()->saidOnTheScreen(),
            means: $line->means(),
            downSays: $line->downSays(),
            upSays: $line->upSays(),
            cautions: $this->sentences($line->cautions()),
            untouched: $this->sentences($line->untouched()),
            measured: $measured instanceof TheLineAsMeasured ? $measured : null,
            cap: $cap instanceof TheCapAsShown ? $cap : null,
            spentCap: $line->spentCap(AsText::of(...), AsText::nothing(...))->said,
            uploadCost: $line->uploadCost(AsText::of(...), AsText::nothing(...))->said,
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): HowTheLineTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): HowTheLineTurnedOutToBe
    {
        return new HowTheLineTurnedOutToBe(
            went: $went,
            standsSaid: '',
            means: '',
            downSays: '',
            upSays: '',
            cautions: [],
            untouched: [],
            measured: null,
            cap: null,
            spentCap: '',
            uploadCost: '',
        );
    }

    /** What the line was measured to carry, with how long ago against the frame's moment. */
    private static function measured(WhatTheLineCarries $carries, Instant $now): TheLineAsMeasured
    {
        $ago = HowLongAgo::since($carries->taken(), $now);
        // In bits a second, which is what a line is sold in and what the
        // stack's own sentences beside these figures say.
        $down = HowFast::of($carries->down());
        $up = HowFast::of($carries->up());

        return new TheLineAsMeasured(
            downFigure: $down->figure,
            downUnit: $down->said,
            upFigure: $up->figure,
            upUnit: $up->said,
            measuredSaid: $carries->measuredAs()->saidOnTheScreen(),
            tunnelSaid: $carries->tunnel()->saidOnTheScreen(),
            agoSaid: $ago->saidOnTheScreen(),
            agoCount: $ago->howManySince($carries->taken(), $now),
        );
    }

    /** A declared cap, with where the month stands where something counted it. */
    private static function cap(AMonthlyCap $cap): TheCapAsShown
    {
        return new TheCapAsShown(
            figure: HowBig::of($cap->monthly())->figure,
            unit: HowBig::of($cap->monthly())->said,
            doesSaid: $cap->does()->saidOnTheScreen(),
            standingSaid: $cap->stands(
                stands: static fn(WhereTheMonthStands $month): AsText => AsText::of($month->saidOnTheScreen()),
                uncounted: static fn(): AsText => AsText::nothing(),
            )->said,
        );
    }

    /**
     * The stack's sentences, as the list a template walks.
     *
     * Collected by hand, for `C10`'s reason: `iterator_to_array` over a list
     * takes an argument that cannot be wrong.
     *
     * @return list<string>
     */
    private function sentences(Remarks $remarks): array
    {
        $said = [];

        foreach ($remarks as $one) {
            $said[] = $one;
        }

        return $said;
    }
}
