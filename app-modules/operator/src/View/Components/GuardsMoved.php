<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AMoveInPlaceAsShown;

use function view;

/** lemonfiber's own guards an act moved onto their plugins for good, one a row. */
final class GuardsMoved extends Component
{
    /** @param list<AMoveInPlaceAsShown> $guards */
    public function __construct(public readonly array $guards) {}

    public function render(): View
    {
        return view('operator::components.guards-moved');
    }
}
