<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * Asks for the next frame at once, and draws nothing.
 *
 * For a screen that read its stack on this frame and owes a second reading,
 * which waits for a frame of its own: a frame reads a stack once.
 */
final class TheNextFrame extends Component
{
    public function render(): View
    {
        return view('design::components.the-next-frame');
    }
}
