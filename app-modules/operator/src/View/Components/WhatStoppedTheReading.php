<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The two states a screen shows instead of its own reading.
 *
 * A session that has ended (`N1-R44`) and an obstacle that stopped the reading
 * (`N1-R10`, `N1-R3`). Both were written out on twelve screens, which is twelve
 * places for one of them to drift and `N1-R1` asks for parity across surfaces
 * rather than parity by everybody remembering.
 *
 * It draws nothing when the reading succeeded, so a screen emits it
 * unconditionally and guards its own content on the same two facts — which is
 * the half of this that is still written out per screen, because the fields
 * live on seven separate answer types with no shape between them. Folding
 * `isSignedIn`, `met` and `remedy` into one value those seven hold is what
 * would put the decision in one place; until then the component owns what is
 * *drawn* and each screen owns whether to draw beside it.
 */
final class WhatStoppedTheReading extends Component
{
    public function __construct(
        public readonly bool $signedIn,
        public readonly string $met,
        public readonly string $remedy,
        public readonly string $signInGoesTo,
        public readonly string $askAgain = 'again()',
    ) {}

    public function render(): View
    {
        return view('operator::components.what-stopped-the-reading');
    }
}
