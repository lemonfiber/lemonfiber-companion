<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

use function view;

/**
 * The same request offered again, where other work held the stack.
 *
 * Drawn beside what stood in the way of an action, and only where that was
 * other work: the stack turned the request away before acting on it, so
 * sending it again changes nothing that the first attempt did. `$tap` is the
 * screen's own method, which sends what was agreed to, unchanged, under a new
 * key, and only when it is tapped.
 */
final class TryAgain extends Component
{
    public function __construct(
        public readonly HowTheReadingWent $went,
        public readonly string $tap,
    ) {}

    public function render(): View
    {
        return view('operator::components.try-again');
    }
}
