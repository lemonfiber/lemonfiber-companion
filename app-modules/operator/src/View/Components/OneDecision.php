<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;

use function view;

/** One decision setup makes, or one thing a change opens: where it stands, what changing it affects, and where it is changed. */
final class OneDecision extends Component
{
    use WritesItsTaps;

    public function __construct(
        public readonly ADecisionAsShown $decision,
        public readonly bool $open,
        public readonly ?WhatAChangeTurnedOutToBe $proposal,
        public readonly bool $waiting,
        public readonly ?string $landingSaid,
        public readonly AnOffer $offer,
    ) {}

    public function render(): View
    {
        return view('operator::components.one-decision');
    }
}
