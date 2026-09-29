<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** The line carrying the weight in a group of them: a name, a count, an obstacle. */
final class Strong extends Component
{
    public function render(): View
    {
        return view('design::components.strong');
    }
}
