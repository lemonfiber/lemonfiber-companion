<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The line that names what follows it.
 *
 * One of the few shapes a screen is built from. Named rather than spelled out
 * at each site because a utility string repeated forty times is forty places
 * for the platform mapping to be decided again — and the
 * decision is the same one every time.
 */
final class Heading extends Component
{
    public function render(): View
    {
        return view('operator::components.heading');
    }
}
