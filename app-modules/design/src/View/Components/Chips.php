<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\HoldsItsSlot;
use Override;

use function view;

/**
 * A row of chips that wraps onto the next line when the screen is narrow.
 *
 * Opened by `chips` and closed by `chips-closes` ({@see HoldsItsSlot}).
 */
final class Chips extends Component
{
    use HoldsItsSlot;

    public function render(): View
    {
        return view('design::components.chips-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('design::components.chips');
    }
}
