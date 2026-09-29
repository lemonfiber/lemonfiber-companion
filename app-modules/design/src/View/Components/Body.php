<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** A line read as it is written. */
final class Body extends Component
{
    public function render(): View
    {
        return view('design::components.body');
    }
}
