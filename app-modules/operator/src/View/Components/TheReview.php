<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;

use function view;

/** One setting open for changing through the stack's review, on every screen that changes one through `config-set`. */
final class TheReview extends Component
{
    use WritesItsTaps;

    public function __construct(
        public readonly string $key,
        public readonly ?WhatAChangeTurnedOutToBe $proposal,
        public readonly bool $waiting,
        public readonly ?string $landingSaid,
        public readonly WhatAJobSaidAsShown $landingSays,
    ) {}

    public function render(): View
    {
        return view('operator::components.the-review');
    }
}
