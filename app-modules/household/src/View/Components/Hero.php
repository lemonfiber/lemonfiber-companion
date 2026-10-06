<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\View\WhatAPosterIsFilledWith;

use function view;

/**
 * The newest title in the house, drawn across Home: a raised 16:9 tile with
 * its name lettered large, Play, and More.
 *
 * The core hands the app no artwork, so the name is the picture, a step
 * larger than on its poster.
 * Play is drawn and cannot be used: the core hands the app no way to play a
 * title, and the reason is said beside it in the app's own words. More opens
 * the title's own screen where the poster would.
 */
final class Hero extends Component
{
    /** What leads the line at the top of the tile, ahead of the year and kind. */
    private const string ABOVE = 'household.hero.above';

    /** What a screen reader says for the tile. */
    private const string READS = 'household.hero.reads';

    /** What a screen reader says for Play. */
    private const string PLAYS = 'household.title.play_named';

    /** What a screen reader says for More. */
    private const string MORE = 'household.hero.more_named';

    /** What a screen reader says for the tile. */
    public readonly string $named;

    /** The line at the top of the tile. */
    public readonly string $above;

    /** How many lines the name may take before the rest is cut. */
    public readonly int $lines;

    /** What a screen reader says for Play, naming the title it would play. */
    public readonly string $playNamed;

    /** What a screen reader says for More, naming the title it opens. */
    public readonly string $moreNamed;

    public function __construct(
        Translator $catalogue,
        public readonly WhatOnePosterSays $poster,
    ) {
        $said = WhatAPosterIsFilledWith::by($catalogue, $poster);
        $named = $catalogue->get(self::READS, ['reads' => $said->line($poster->reads, $poster->titled)]);
        $above = $catalogue->get(self::ABOVE, ['line' => $said->line($poster->above, '')]);
        $plays = $catalogue->get(self::PLAYS, ['title' => $poster->titled]);
        $more = $catalogue->get(self::MORE, ['title' => $poster->titled]);

        $this->named = is_string($named) ? $named : $poster->titled;
        $this->above = is_string($above) ? $above : '';
        $this->lines = $poster->lettered->linesOnTheHero();
        $this->playNamed = is_string($plays) ? $plays : $poster->titled;
        $this->moreNamed = is_string($more) ? $more : $poster->titled;
    }

    public function render(): View
    {
        return view('household::components.hero');
    }
}
