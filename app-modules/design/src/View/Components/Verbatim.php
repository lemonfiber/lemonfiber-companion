<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/** Something a machine wrote and a person may have to copy: an address, a code, a fingerprint. */
final class Verbatim extends Component
{
    public function render(): View
    {
        return view('design::components.verbatim');
    }
}
