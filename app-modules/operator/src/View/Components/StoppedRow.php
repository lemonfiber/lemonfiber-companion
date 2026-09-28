<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AStoppageAsShown;

use function view;

/**
 * One row of what stopped moving, drawn the same under either heading.
 *
 * One component because a stuck row and a slow row are one shape: what kind,
 * what, how many, the service's words and how long. Written twice, the two
 * lists would come to draw the same row differently.
 */
final class StoppedRow extends Component
{
    /**
     * @param AStoppageAsShown $row   the row, as the presenter flattened it
     * @param string           $trace where following it leads, or empty where the row stands for several items
     */
    public function __construct(
        public readonly AStoppageAsShown $row,
        public readonly string $trace,
    ) {}

    public function render(): View
    {
        return view('operator::components.stopped-row');
    }
}
