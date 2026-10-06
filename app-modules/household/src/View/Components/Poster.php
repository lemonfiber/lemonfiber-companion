<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\View\WhatAPosterIsFilledWith;

use function view;

/**
 * One title on a member's Home, drawn as a poster: a raised 2:3 tile with a
 * line at the top — its year and kind, or where a request of theirs stands —
 * and its name lettered at the bottom.
 *
 * The core hands the app no artwork, so the name is the picture, set at the
 * step its length allows. Nothing is written under the tile.
 *
 * **Read as one element.** A screen reader hears the label once, and not the
 * words drawn on the tile a second time: the line at the top would otherwise
 * come before the name, and a name cut short on the tile would be cut short
 * for the listener too.
 */
final class Poster extends Component
{
    /** What a screen reader says for the tile. */
    public readonly string $named;

    /** The line at the top of the tile. */
    public readonly string $above;

    /** How many lines the name may take before the rest is cut. */
    public readonly int $lines;

    public function __construct(
        Translator $catalogue,
        public readonly WhatOnePosterSays $poster,
    ) {
        $said = WhatAPosterIsFilledWith::by($catalogue, $poster);

        $this->named = $said->line($poster->reads, $poster->titled);
        $this->above = $said->line($poster->above, '');
        $this->lines = $poster->lettered->linesAtMost();
    }

    public function render(): View
    {
        return view('household::components.poster');
    }
}
