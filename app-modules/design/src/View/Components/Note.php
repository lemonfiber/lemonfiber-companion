<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** The quieter line, read after the one above it. */
final class Note extends Component
{
    public function render(): View
    {
        return view('design::components.note');
    }
}
