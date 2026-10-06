<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatOneHoldingSays;

use function view;

/**
 * One title on a member's shelf, drawn as a poster: a raised 2:3 tile with
 * its year and kind at the top and its title lettered at the bottom.
 *
 * The core hands the app no artwork, so the title is the picture, set at the
 * step its length allows. Nothing is written under the tile.
 *
 * **Read as one element.** A screen reader hears the title, its kind and its
 * year once, from the label, and not the words drawn on the tile a second
 * time: the year and kind at the top would otherwise come before the title,
 * and a title cut short on the tile would be cut short for the listener too.
 */
final class Poster extends Component
{
    /** What the tile says for a dated holding: title, kind, year. */
    private const string READS = 'household.poster.reads';

    /** What the tile says for a holding the core could not date. */
    private const string READS_UNDATED = 'household.poster.reads_undated';

    /** The line at the top of a dated holding's tile. */
    private const string ABOVE = 'household.poster.above';

    /** The line at the top of an undated holding's tile. */
    private const string ABOVE_UNDATED = 'household.poster.above_undated';

    /** What a screen reader says for the tile. */
    public readonly string $named;

    /** The line at the top of the tile: the year and the kind, or the kind alone. */
    public readonly string $above;

    /** How many lines the title may take before the rest is cut. */
    public readonly int $lines;

    public function __construct(
        Translator $catalogue,
        public readonly WhatOneHoldingSays $holding,
    ) {
        $kind = $catalogue->get($holding->medium);
        $filling = [
            'title' => $holding->titled,
            'kind' => is_string($kind) ? $kind : $holding->medium,
            'year' => $holding->year,
        ];
        $isDated = $holding->year !== '';

        $named = $catalogue->get($isDated ? self::READS : self::READS_UNDATED, $filling);
        $above = $catalogue->get($isDated ? self::ABOVE : self::ABOVE_UNDATED, $filling);

        $this->named = is_string($named) ? $named : $holding->titled;
        $this->above = is_string($above) ? $above : $filling['kind'];
        $this->lines = $holding->lettered->linesAtMost();
    }

    public function render(): View
    {
        return view('household::components.poster');
    }
}
