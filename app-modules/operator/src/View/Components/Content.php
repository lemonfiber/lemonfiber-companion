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
 * It opens at its top, or at its end where what matters is last: a service's
 * lines arrive oldest first, and the one that says why it fell over is the
 * last, so that screen opens there and is scrolled back from.
 *
 * Opened by the template `content` and closed by `content-closes`.
 * {@see HoldsItsSlot} says why a container that holds a slot is two
 * templates.
 */
final class Content extends Component
{
    use HoldsItsSlot;

    public function __construct(
        public readonly bool $fromTheEnd = false,
    ) {}

    public function render(): View
    {
        return view('operator::components.content-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('operator::components.content', ['anchor' => $this->fromTheEnd ? 'bottom' : null]);
    }
}
