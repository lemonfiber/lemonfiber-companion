<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ALastRunAsShown;

use function view;

/**
 * How a run-and-exit service's run ended where it failed: its exit, and the last lines it wrote, on the schedule and after a run at once.
 */
final class HowARunEnded extends Component
{
    public function __construct(public readonly ALastRunAsShown $last) {}

    public function render(): View
    {
        return view('operator::components.how-a-run-ended');
    }
}
