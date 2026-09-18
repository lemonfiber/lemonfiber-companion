<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\WhatOneRequestSays;

/**
 * One request the household made, as the row a screen lists it on.
 *
 * The sibling of {@see HowAStalledItemReads}: one value in, one row out, and
 * nothing else needed to do it. A request takes two methods where a stalled
 * item takes one, because it answers its refusal and its size separately.
 */
final readonly class HowARequestReads
{
    /**
     * Fold one request into the fields a row needs.
     *
     * The `either()` is answered here rather than in the screen, so a screen
     * showing requests is a loop over this and not a fold per row — and every
     * arm builds the whole row, which is
     * {@see \Modules\Operator\Internal\ViewModels\WhatOneFindingSays}' shape and
     * the reason a size can never reach a template without its label.
     */
    public function in(Wanted $wanted): WhatOneRequestSays
    {
        $standing = $wanted->standing();

        return $wanted->refusal(
            was: fn(TurnedDown $why): WhatOneRequestSays => $this->built($wanted, $standing, $why),
            wasNot: fn(): WhatOneRequestSays => $this->built($wanted, $standing),
        );
    }

    /**
     * The row itself, once the refusal has been answered.
     *
     * Split from {@see self::in()} so the size fold below happens once rather
     * than once per arm — the two questions are independent and writing them
     * nested would be four arms where there are two facts.
     */
    private function built(Wanted $wanted, Waiting $standing, ?TurnedDown $why = null): WhatOneRequestSays
    {
        $reason = $why instanceof TurnedDown ? $why->reason() : '';
        $at = $why instanceof TurnedDown ? $why->when(
            then: static fn(string $when): AsText => AsText::of($when),
            unstated: static fn(): AsText => AsText::of(''),
        )->said : '';

        return $wanted->size()->either(
            measured: static fn(int $bytes): WhatOneRequestSays => new WhatOneRequestSays(
                number: $wanted->number(),
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_measured',
                sizeFigure: HowBig::of($bytes)->figure,
                sizeUnit: HowBig::of($bytes)->said,
                refusedReason: $reason,
                refusedAt: $at,
            ),
            guessed: static fn(int $bytes): WhatOneRequestSays => new WhatOneRequestSays(
                number: $wanted->number(),
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_guessed',
                sizeFigure: HowBig::of($bytes)->figure,
                sizeUnit: HowBig::of($bytes)->said,
                refusedReason: $reason,
                refusedAt: $at,
            ),
            unknown: static fn(): WhatOneRequestSays => new WhatOneRequestSays(
                number: $wanted->number(),
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_unknown',
                // Nothing to render the sentence with, and *we do not know* is
                // a sentence that needs nothing. The figure is unread on this
                // arm — its key names no placeholder — which is why zero here
                // cannot become "0 bytes" beside a request for a whole season,
                // the outcome that is worse than saying nothing.
                sizeFigure: 0,
                sizeUnit: '',
                refusedReason: $reason,
                refusedAt: $at,
            ),
        );
    }
}
