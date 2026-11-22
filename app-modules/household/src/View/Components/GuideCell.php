<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use function count;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatACellSays;

use function view;

/**
 * One stretch of a channel's row of the guide, as wide as it runs: a programme with when it starts and ends beneath, the cut-off edge of one, or several short ones together with how many and the time they run across.
 *
 * **Read as one element**, for {@see Poster}'s reason: a screen reader hears
 * the channel, the programme, its times and whether it is on now once, and
 * for several together, how many and the time they run across.
 */
final class GuideCell extends Component
{
    /** What a screen reader says for the cell. */
    public readonly string $named;

    /** How many programmes it holds, as the cell letters it where it holds several. */
    public readonly string $counted;

    public function __construct(Translator $catalogue, public readonly WhatACellSays $cell, string $channel)
    {
        $this->named = self::said($catalogue, $cell, $channel);
        $this->counted = $catalogue->choice('household.guide.together', count($cell->together));
    }

    /** What a screen reader says for a cell, or for a programme in a list of several. */
    public static function said(Translator $catalogue, WhatACellSays $cell, string $channel): string
    {
        if ($cell->isSeveral()) {
            return $catalogue->choice('household.guide.reads_together', count($cell->together), ['times' => $cell->times]);
        }

        $key = $cell->isOnNow ? 'household.guide.reads_on_now' : 'household.guide.reads_later';
        $said = $catalogue->get($key, ['channel' => $channel, 'programme' => $cell->title, 'times' => $cell->times]);

        return is_string($said) ? $said : $key;
    }

    public function render(): View
    {
        return view('household::components.guide-cell');
    }
}
