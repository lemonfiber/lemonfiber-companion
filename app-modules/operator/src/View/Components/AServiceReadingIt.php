<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AServiceReadingItAsShown;

use function view;

/** One service reading a setting or a secret, with the variable it reads it into and the default it declares. */
final class AServiceReadingIt extends Component
{
    /** @param AServiceReadingItAsShown $reader the reader, as the presenter flattened it */
    public function __construct(public readonly AServiceReadingItAsShown $reader) {}

    public function render(): View
    {
        return view('operator::components.a-service-reading-it');
    }
}
