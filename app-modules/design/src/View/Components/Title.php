<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** The one line a screen leads with: its verdict, or what it is for. */
final class Title extends Component
{
    public function render(): View
    {
        return view('design::components.title');
    }
}
