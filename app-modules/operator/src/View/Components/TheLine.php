<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\HowTheLineTurnedOutToBe;

use function view;

/**
 * One reading of how a stack shares its line, drawn the same where it is read and where a change to it is rehearsed.
 */
final class TheLine extends Component
{
    /** @param HowTheLineTurnedOutToBe $line the reading, as the presenter flattened it */
    public function __construct(public readonly HowTheLineTurnedOutToBe $line) {}

    public function render(): View
    {
        return view('operator::components.the-line');
    }
}
