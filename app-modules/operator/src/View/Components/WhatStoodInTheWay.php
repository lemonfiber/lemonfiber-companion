<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

use function view;

/**
 * What stood in the way of a reading, and what to do about it, as lines in
 * whatever column it is drawn in.
 *
 * Two of those, and they read differently: a session that has ended
 * is a screen with a way back in, and an obstacle that stopped the reading
 * is a sentence about a machine with the action still on
 * offer. Which of the two a refusal is, is not decided here — it is decided in
 * {@see HowTheReadingWent}, and this draws whichever arrived.
 *
 * **It opens no container.** A screen draws it inside content it has already
 * opened, where one of several readings did not come back; a screen with
 * nothing else to draw has {@see WhatStoppedTheReading}, which is this inside
 * {@see Content}. Content is a scroll view, so drawing that inside content
 * would put a scroll view inside a scroll view.
 *
 * **Asking again is the screen's to place.** It is drawn here unless the
 * screen hands no method to ask with, which a screen does where its column
 * already offers asking again: one action, one control.
 */
final class WhatStoodInTheWay extends Component
{
    public function __construct(
        public readonly HowTheReadingWent $went,
        public readonly string $signInGoesTo,
        public readonly string $askAgain = 'again()',
        public readonly bool $settingsWouldNotOpen = false,
    ) {}

    public function render(): View
    {
        return view('operator::components.what-stood-in-the-way');
    }
}
