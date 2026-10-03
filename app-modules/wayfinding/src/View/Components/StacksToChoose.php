<?php

declare(strict_types=1);

namespace Modules\Wayfinding\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Wayfinding\Api\TheStacksToChooseFrom;

use function view;

/**
 * The list of stacks, as a sheet over a screen about a stack.
 *
 * Drawn under the stack's name in the top bar, and on its own on a screen
 * whose top bar says something else, where the menu's first row opens it.
 * Its rows are drawn only while it is open.
 */
final class StacksToChoose extends Component
{
    /**
     * @param TheStacksToChooseFrom      $stacks   every stack to choose from, while the list is open
     * @param bool                       $choosing whether the list is open
     */
    public function __construct(
        public readonly TheStacksToChooseFrom $stacks,
        public readonly bool $choosing,
    ) {}

    public function render(): View
    {
        return view('wayfinding::components.stacks-to-choose');
    }
}
