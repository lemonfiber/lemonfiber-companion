<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The services an operator may choose to answer a capability, one tap each.
 *
 * A tap asks what the choice would come to and changes nothing; the screen
 * draws that before anything is agreed to.
 */
final class WhoMayAnswer extends Component
{
    /** @param list<string> $choices the services that may be chosen, in the order the row shows its claimants */
    public function __construct(public readonly string $by, public readonly string $capability, public readonly array $choices) {}

    public function render(): View
    {
        return view('operator::components.who-may-answer');
    }
}
