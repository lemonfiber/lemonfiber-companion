<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * Something the operator can do that is not the way forward.
 *
 * {@see Action}'s quieter twin, and the reason there has to be one: the
 * platform paints every button the same fill and honours no per-instance
 * colour, so two filled bars side by side make neither of them the way forward.
 * `DES-R25` refuses the override that would tell them apart by colour and
 * `DES-R15` measures the accent as text at 1.6:1, which is why there is no
 * third option. What is left is **form** — a filled bar for the one thing to
 * do, and a line of words for the way past it.
 *
 * The first run is where that was written down and not done: the step that
 * offers *go on* and *skip* carried a comment saying the second should be
 * quieter, under two identical buttons.
 *
 * **It is a control, and it announces itself as one.** A tappable line has no
 * `label` of its own, so `F5` reaches it through `a11y-label` — the same text,
 * because what the eye reads and what a reader hears are the same words here.
 *
 * **A road can be quiet too**, which this took a screen to learn. The first
 * version of it said that a decision belongs on the screen and a road to
 * another one is a road, and roads are buttons. Pairing is where that comes
 * apart: *open the camera*, *type the code instead* and *back to your stacks*
 * are three filled bars, two of them roads, and the frame has one thing it
 * wants an operator to do. What decides is not whether a control navigates —
 * it is whether it is the way forward.
 *
 * `$tap` and `$goes` behave as {@see Action}'s do: exactly one is set, because
 * a control that does two things on one press is two controls sharing a name.
 */
final class QuietAction extends Component
{
    public function __construct(
        public readonly string $label,
        public readonly string $tap = '',
        public readonly string $goes = '',
    ) {}

    public function render(): View
    {
        return view('operator::components.quiet-action');
    }
}
