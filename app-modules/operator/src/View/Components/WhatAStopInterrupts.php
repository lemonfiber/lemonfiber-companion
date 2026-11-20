<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\WhatIsComingDownAsShown;

use function view;

/**
 * What an action that stops services would interrupt, drawn before it is agreed to.
 */
final class WhatAStopInterrupts extends Component
{
    /** @param WhatIsComingDownAsShown $comingDown what the stack said is still coming down, or that it could not be asked */
    public function __construct(public readonly WhatIsComingDownAsShown $comingDown) {}

    public function render(): View
    {
        return view('operator::components.what-a-stop-interrupts');
    }
}
