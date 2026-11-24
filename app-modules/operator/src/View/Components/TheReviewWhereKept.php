<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;
use Modules\Operator\Internal\WhereAStackIs;

use function view;

/** One decision the settings store keeps, open for changing through the stack's review of `settings-set`. */
final class TheReviewWhereKept extends Component
{
    use WritesItsTaps;

    public function __construct(
        public readonly string $key,
        public readonly TheSettingChangeTurnedOutToBe $going,
        public readonly WhatAJobSaidAsShown $said,
        public readonly bool $settingsWouldNotOpen,
        public readonly WhereAStackIs $goes,
    ) {}

    public function render(): View
    {
        return view('operator::components.the-review-where-kept');
    }
}
