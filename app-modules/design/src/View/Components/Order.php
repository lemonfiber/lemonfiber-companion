<?php

declare(strict_types=1);

namespace Modules\Design\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * A list somebody puts in order by dragging its rows, or with a screen reader's
 * move actions.
 *
 * Each row names its key and the name it shows. `moveUp` and `moveDown` are the
 * catalogue lines a screen reader offers to move a row, each given the row's
 * name. The screen's `change` method hears the keys in their new order, as
 * `Lemonfiber\Native\Reorderable::keysIn()` reads them.
 */
final class Order extends Component
{
    /** @param list<array{key: string, name: string}> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly string $change,
        public readonly string $label,
        public readonly string $moveUp,
        public readonly string $moveDown,
    ) {}

    public function render(): View
    {
        return view('design::components.order');
    }
}
