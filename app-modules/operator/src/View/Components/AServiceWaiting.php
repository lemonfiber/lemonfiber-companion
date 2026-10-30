<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AServiceWaitingAsShown;

use function view;

/** A service the stack holds back, and everything it waits for. */
final class AServiceWaiting extends Component
{
    /** @param AServiceWaitingAsShown $waiting the service, as the presenter flattened it */
    public function __construct(public readonly AServiceWaitingAsShown $waiting) {}

    public function render(): View
    {
        return view('operator::components.a-service-waiting');
    }
}
