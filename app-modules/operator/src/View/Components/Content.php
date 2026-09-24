<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\View\HoldsItsSlot;
use Override;

use function view;

/**
 * Where a screen's content sits: one padded column, holding the slot.
 *
 * Opened by the template `content` and closed by `content-closes`.
 * {@see HoldsItsSlot} says why a container that holds a slot is two
 * templates.
 */
final class Content extends Component
{
    use HoldsItsSlot;

    public function render(): View
    {
        return view('operator::components.content-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('operator::components.content');
    }
}
