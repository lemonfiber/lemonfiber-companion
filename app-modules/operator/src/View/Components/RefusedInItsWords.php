<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;

use function view;

/**
 * A stack's refusal in its own words, drawn the same on every screen that asked.
 *
 * One component because a refused listing and a refused yes are one shape: the
 * stack's sentence, what it means and what it named. Written per screen, one
 * of them would come to draw what was named where another does not.
 */
final class RefusedInItsWords extends Component
{
    /** @param ARefusalAsShown $refused the refusal, as the presenter flattened it */
    public function __construct(public readonly ARefusalAsShown $refused) {}

    public function render(): View
    {
        return view('operator::components.refused-in-its-words');
    }
}
