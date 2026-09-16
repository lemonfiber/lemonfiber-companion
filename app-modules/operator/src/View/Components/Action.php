<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * Something the operator can do, or somewhere they can go.
 *
 * Both, because on a screen they are the same object and the difference is
 * which of two attributes carries it. Writing them out at each of forty-eight
 * sites is forty-eight places to leave a control without the label `F5` asks
 * for — and the label is the one part a component can make impossible to
 * forget, by taking it as a constructor argument that has no default.
 *
 * `$tap` is the screen's own method, named for the navigation stack to call.
 * `$goes` is a route. Exactly one is set: a control that does two things on one
 * press is two controls sharing a label.
 *
 * `$disabled` is offered rather than assumed: `N1-R3` refuses a screen that
 * takes an action away because it cannot reach a stack, so what this is for is
 * a control whose preconditions are on the device — a camera that has not been
 * allowed, a field with nothing typed in it yet.
 */
final class Action extends Component
{
    public function __construct(
        public readonly string $label,
        public readonly string $tap = '',
        public readonly string $goes = '',
        public readonly bool $disabled = false,
    ) {}

    public function render(): View
    {
        return view('operator::components.action');
    }
}
