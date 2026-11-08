<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ACheckAfterAStartAsShown;

use function view;

/**
 * One run of the check after a start: when it ran, what it came to, what did not come back, the tunnel and the forwarded port.
 *
 * When it ran is said as the check after the latest restart or as an earlier
 * one, as the presenter decided.
 */
final class ACheckAfterAStart extends Component
{
    public function __construct(
        public readonly ACheckAfterAStartAsShown $check,
    ) {}

    public function render(): View
    {
        return view('operator::components.a-check-after-a-start');
    }
}
