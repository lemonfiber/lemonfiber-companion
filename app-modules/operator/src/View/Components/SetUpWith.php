<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;

use function view;

/**
 * What setup settled, as facts about the stack, on its settings and on its own screen.
 */
final class SetUpWith extends Component
{
    /** @param WhatSetupSettledAsShown|null $settled what setup settled, or nothing while the next frame reads it */
    public function __construct(public readonly ?WhatSetupSettledAsShown $settled) {}

    public function render(): View
    {
        return view('operator::components.set-up-with');
    }
}
