<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;

use function view;

/**
 * One row of a member's shelf: its heading, and its posters side by side,
 * scrolled sideways where there are more than the phone is wide.
 *
 * The row draws every poster it was handed and no more: what it holds is what
 * the shelf read returned, so nothing here leads on to a longer list. A row
 * handed none is not drawn, its heading included: a heading over nothing
 * reads as a row that did not load.
 */
final class ShelfRow extends Component
{
    public function __construct(public readonly WhatAShelfRowSays $row) {}

    public function shouldRender(): bool
    {
        return $this->row->posters !== [];
    }

    public function render(): View
    {
        return view('household::components.shelf-row');
    }
}
