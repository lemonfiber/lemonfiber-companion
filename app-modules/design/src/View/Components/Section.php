<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\View\HoldsItsSlot;
use Override;

use function view;

/**
 * A group of rows on one card, under an optional label.
 *
 * Opened by `section` and closed by `section-closes`, so the slot is drawn on
 * the card ({@see HoldsItsSlot}).
 */
final class Section extends Component
{
    use HoldsItsSlot;

    public function __construct(public readonly string $label = '') {}

    public function render(): View
    {
        return view('design::components.section-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('design::components.section', ['label' => $this->label]);
    }
}
