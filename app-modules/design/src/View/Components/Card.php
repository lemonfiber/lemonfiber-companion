<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\HoldsItsSlot;
use Override;

use function view;

/**
 * Lines that belong together, on a card of their own: one finding, one item.
 *
 * Opened by `card` and closed by `card-closes` ({@see HoldsItsSlot}).
 */
final class Card extends Component
{
    use HoldsItsSlot;

    public function render(): View
    {
        return view('design::components.card-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('design::components.card');
    }
}
