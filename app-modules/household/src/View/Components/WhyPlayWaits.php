<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatPlayingSays;

use function view;

/**
 * Why Play cannot be pressed, as the line beside it.
 *
 * {@see WhatStoodInTheWay}'s one decision, for Play: the core's own words are
 * drawn as they were written, and only this app's keys go through the
 * catalogue.
 */
final class WhyPlayWaits extends Component
{
    /** The line, as it is drawn. */
    public readonly string $said;

    public function __construct(Translator $catalogue, WhatPlayingSays $playing)
    {
        $looked = $catalogue->get($playing->why);
        $this->said = $playing->isInTheCoresWords || ! is_string($looked) ? $playing->why : $looked;
    }

    public function render(): View
    {
        return view('household::components.why-play-waits');
    }
}
