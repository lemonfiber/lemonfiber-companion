<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;

use function view;

/**
 * One thing a stack runs, as a row ending in its port.
 *
 * The port leads, then the thing's name with what is said about how it
 * stands under it, then a figure about it set as a stamp. A row that goes
 * somewhere or does something is one target the height a thumb needs, with a
 * chevron at its end, and is read aloud by `answersTo` where its name alone
 * would be ambiguous. Rows are told apart by the hairline under each, as the
 * operator's theme draws a list.
 */
final class PortRow extends Component
{
    public readonly string $named;

    /** The chevron's colour, which says less than the words beside it. */
    public readonly string $chevron;

    public function __construct(
        WhichThemeIsOnTheGlass $glass,
        public readonly string $tone,
        public readonly string $name,
        public readonly string $said = '',
        public readonly string $figure = '',
        public readonly string $tap = '',
        public readonly string $goes = '',
        string $answersTo = '',
    ) {
        $this->named = $answersTo === '' ? $name : $answersTo;
        $this->chevron = ThemeToken::Muted->in($glass->whose());
    }

    public function render(): View
    {
        return view('operator::components.port-row');
    }
}
