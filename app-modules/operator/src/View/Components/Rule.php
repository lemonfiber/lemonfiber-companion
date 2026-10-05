<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * A hairline between two entries of a list, one point wide in the line role.
 *
 * The operator's theme draws its rows on hairlines rather than on cards, and
 * the platform's own divider is the one line both renderers draw at a point
 * in a colour handed to it.
 */
final class Rule extends Component
{
    public function render(): View
    {
        return view('operator::components.rule');
    }
}
