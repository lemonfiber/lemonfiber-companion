<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatACellSays;

use function view;

/**
 * One programme in a channel's row of the guide: its title, and when it starts and ends beneath, as wide as it runs.
 *
 * **Read as one element**, for {@see Poster}'s reason: a screen reader hears
 * the channel, the programme, its times and whether it is on now once.
 */
final class GuideCell extends Component
{
    /** What a screen reader says for the cell. */
    public readonly string $named;

    public function __construct(Translator $catalogue, public readonly WhatACellSays $cell, string $channel)
    {
        $key = $cell->isOnNow ? 'household.guide.reads_on_now' : 'household.guide.reads_later';
        $said = $catalogue->get($key, ['channel' => $channel, 'programme' => $cell->title, 'times' => $cell->times]);
        $this->named = is_string($said) ? $said : $key;
    }

    public function render(): View
    {
        return view('household::components.guide-cell');
    }
}
