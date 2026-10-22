<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AMoveInPlaceAsShown;

use function view;

/** The moves in place an update would take or took, or a start named, one a row. */
final class MovesInPlace extends Component
{
    /**
     * @param list<AMoveInPlaceAsShown> $moves
     * @param string                    $after what is said under the last move, or empty for nothing
     */
    public function __construct(public readonly array $moves, public readonly string $after = '') {}

    public function render(): View
    {
        return view('operator::components.moves-in-place');
    }
}
