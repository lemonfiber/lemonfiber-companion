<?php

declare(strict_types=1);

namespace Modules\Wayfinding\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Wayfinding\Api\TheStacksToChooseFrom;

use function view;

/**
 * The top of a screen about a stack: the stack's name, which opens the list
 * of stacks.
 *
 * The name is a control, so a screen reader reads it as one and says the
 * whole name where the bar shortens it. The list itself is
 * {@see StacksToChoose}.
 *
 * It draws the platform's back control where the screen was opened over
 * another, and only there: the screen says which, from the same reading its
 * menu is placed by.
 */
final class StackOpens extends Component
{
    /**
     * @param string                     $title    the stack's name
     * @param TheStacksToChooseFrom      $stacks   every stack to choose from, while the list is open
     * @param bool                       $choosing whether the list is open
     * @param bool                       $back     whether the screen was opened over another
     */
    public function __construct(
        public readonly string $title,
        public readonly TheStacksToChooseFrom $stacks,
        public readonly bool $choosing,
        public readonly bool $back,
    ) {}

    public function render(): View
    {
        return view('wayfinding::components.stack-opens');
    }
}
