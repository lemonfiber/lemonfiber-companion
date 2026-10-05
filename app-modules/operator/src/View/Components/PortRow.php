<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;

use function sprintf;
use function view;

/**
 * One thing a stack runs, as a row ending in its port.
 *
 * The port leads, then the thing's name with what is said about how it
 * stands under it, then a figure about it set as a stamp. A row that goes
 * somewhere or does something is one target the height a thumb needs, with a
 * chevron at its end. A target is read aloud as one thing, so it is read as
 * its name and what is said about it, or by `answersTo` where that alone
 * would be ambiguous. Rows are told apart by the hairline under each, as the
 * operator's theme draws a list.
 */
final class PortRow extends Component
{
    /** What stands between two things said on one line, in any language. */
    public const string BETWEEN = ' · ';

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
        $this->named = match (true) {
            $answersTo !== '' => $answersTo,
            $said !== '' => sprintf('%s%s%s', $name, self::BETWEEN, $said),
            default => $name,
        };
        $this->chevron = ThemeToken::Muted->in($glass->whose());
    }

    public function render(): View
    {
        return view('operator::components.port-row');
    }
}
