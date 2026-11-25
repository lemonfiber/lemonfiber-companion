<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;

use function view;

/** The backup or restore running on a stack, as its reading says: how much of how much, the part now, and whether it is paced or stalled. */
final class ACopyRunning extends Component
{
    public function __construct(public readonly ACopyRunningAsShown $copy) {}

    public function render(): View
    {
        return view('operator::components.a-copy-running');
    }
}
