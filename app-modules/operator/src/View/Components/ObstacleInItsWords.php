<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnObstacleAsShown;

use function view;

/**
 * What the core named in the way of a reading, drawn the same on every screen of the operator's that shows one.
 */
final class ObstacleInItsWords extends Component
{
    /** @param AnObstacleAsShown $obstacle the obstacle, as the presenter flattened it */
    public function __construct(public readonly AnObstacleAsShown $obstacle) {}

    public function render(): View
    {
        return view('operator::components.obstacle-in-its-words');
    }
}
