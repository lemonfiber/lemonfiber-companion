<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\View\HoldsItsSlot;
use Override;

use function view;

/**
 * One thing in a list of them, and what is said about it.
 *
 * One of the few shapes a screen is built from. Named rather than spelled out
 * at each site because a utility string repeated forty times is forty places
 * for the platform mapping to be decided again — and the
 * decision is the same one every time.
 *
 * Opened by the template `entry` and closed by `entry-closes`.
 * {@see HoldsItsSlot} says why a container that holds a slot is two
 * templates.
 */
final class Entry extends Component
{
    use HoldsItsSlot;

    public function render(): View
    {
        return view('operator::components.entry-closes');
    }

    #[Override]
    protected function opens(): View
    {
        return view('operator::components.entry');
    }
}
