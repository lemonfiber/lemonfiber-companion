<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * A time, a version or an identifier said beside the words about it.
 *
 * Set in the figures face at the caption size, in the faintest text role: it
 * is read after the line it belongs to rather than copied off the screen,
 * which is what `verbatim` is for. It can be read aloud as something other
 * than what it shows, where what fits is a shortening a listener is owed in
 * full.
 */
final class Stamp extends Component
{
    /** @param string $answersTo what a screen reader says for it, where that is not what it shows */
    public function __construct(public readonly string $answersTo = '') {}

    public function render(): View
    {
        return view('operator::components.stamp');
    }
}
