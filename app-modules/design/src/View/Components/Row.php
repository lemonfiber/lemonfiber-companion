<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * One row on a section's card: a line, what it says underneath, and what is at
 * its end.
 *
 * A row that goes somewhere or does something carries a chevron and is the
 * platform's own list row, which honours the name a screen reader is given
 * and is 56 wide-pixels tall. A row that does neither shows its trailing
 * value instead. `answersTo` is the name a reader hears where the headline
 * alone would be ambiguous, as in a list of rows that each say "Open".
 */
final class Row extends Component
{
    public readonly string $named;

    public function __construct(
        public readonly string $headline,
        public readonly string $supporting = '',
        public readonly string $trailing = '',
        public readonly string $tap = '',
        public readonly string $goes = '',
        string $answersTo = '',
    ) {
        $this->named = $answersTo === '' ? $headline : $answersTo;
    }

    public function render(): View
    {
        return view('design::components.row');
    }
}
