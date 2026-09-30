<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The quieter line, read after the one above it.
 *
 * It can be read aloud as something other than what it shows, where what fits
 * on the line is a shortening of something a listener is owed in full.
 */
final class Note extends Component
{
    /** @param string $answersTo what a screen reader says for it, where that is not what it shows */
    public function __construct(public readonly string $answersTo = '') {}

    public function render(): View
    {
        return view('design::components.note');
    }
}
