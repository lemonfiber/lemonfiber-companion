<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\WhereAStackIs;

use function view;

/**
 * The reason a screen has nothing of its own to draw, as the screen's whole
 * body: {@see WhatStoodInTheWay} inside {@see Content}.
 *
 * Only where nothing else is drawn. Inside content a screen has already
 * opened, the screen draws {@see WhatStoodInTheWay} alone, because this opens
 * a scroll view of its own.
 *
 * **The screen owns the branch and this owns one arm of it.** A template says
 * `@`if with `cameBack()` and puts this in the `@`else, so the two arms are
 * exclusive because Blade made them exclusive. A guard of its own — deciding
 * for itself whether there was anything to say — would be a second expression
 * of one rule, and two expressions can disagree: both silent is a blank screen,
 * both drawing is the obstacle and the content at once.
 *
 * **It cannot take the content as a slot, and that is a fact about the
 * renderer rather than a preference.** Blade renders a slot before the
 * component's own template runs, and the native renderer collects the elements
 * as they render — so a slot dropped by an `@`if is still in the tree the
 * device draws. A screen built that way draws both arms and no template rule
 * can see it, because a template rule reads Blade as text.
 * {@see \Tests\Support\WhatTheDeviceWouldDraw} is what sees it.
 */
final class WhatStoppedTheReading extends Component
{
    public function __construct(
        public readonly HowTheReadingWent $went,
        public readonly WhereAStackIs $goes,
        public readonly bool $settingsWouldNotOpen = false,
    ) {}

    public function render(): View
    {
        return view('operator::components.what-stopped-the-reading');
    }
}
