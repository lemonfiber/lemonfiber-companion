<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnAffectedItemAsShown;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;

use function view;

/**
 * Somebody to ask, offered at the foot of a list where a card knew of nothing to try.
 *
 * Once per list rather than once per card: the report it leads to is about
 * the whole machine, so every card that knew of nothing to try is answered by
 * the same act, and one control for it is one name a reader hears.
 */
final class SomebodyToAsk extends Component
{
    /** Whether any card in the list said there was nothing to try. */
    public readonly bool $isOwed;

    /**
     * @param list<AnAffectedItemAsShown|WhatOneFindingSays> $cards every card the list draws
     * @param string                                         $goes  where a report for somebody helping is put together
     */
    public function __construct(array $cards, public readonly string $goes)
    {
        $isOwed = false;

        foreach ($cards as $card) {
            $isOwed = $isOwed || $card->saysNothingToTry();
        }

        $this->isOwed = $isOwed;
    }

    public function render(): View
    {
        return view('operator::components.somebody-to-ask');
    }
}
