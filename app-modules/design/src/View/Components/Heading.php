<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** The line that names what follows it. */
final class Heading extends Component
{
    public function render(): View
    {
        return view('design::components.heading');
    }
}
