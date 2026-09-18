<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\WhereTheFirstRunIs;

use function view;

/**
 * One step of the first run: where somebody is, what it says, and the way on.
 *
 * A first run that is one frame is refused — three sentences and two
 * buttons at once, with nothing saying which of them somebody is supposed to
 * read first. A sequence says one thing per frame and states its own end, which
 * is the difference between being told something and being handed a wall.
 *
 * **It takes the step rather than the sentences.** Every step has to
 * say which it is and how many there are, and a component handed a number and a
 * total is a component two call sites can disagree with. {@see
 * WhereTheFirstRunIs} counts its own cases, so a fourth step changes the
 * denominator everywhere by existing.
 *
 * **The way out is a method name, not a route.** Leaving lands on
 * pairing rather than on nothing, and pairing is the last step of this same
 * sequence — so leaving is a move within the screen and not a navigation. A
 * route here would have been a second spelling of somewhere this screen already
 * is, and the operator would have arrived at it with the counter still reading
 * one of three.
 */
final class FirstRun extends Component
{
    public function __construct(
        public readonly WhereTheFirstRunIs $at,
        public readonly string $on,
        public readonly string $leave,
    ) {}

    public function render(): View
    {
        return view('operator::components.first-run');
    }
}
