<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AClaimantAsShown;

use function view;

/**
 * Every service that claims a capability, each with where it came from.
 *
 * One component for a contest and a settled ask alike, so both draw a claimant
 * the same way: its name, and where it came from in the line every service's
 * origin is drawn in.
 */
final class ClaimedBy extends Component
{
    /** @param list<AClaimantAsShown> $claimants in the order they are handed */
    public function __construct(public readonly array $claimants) {}

    public function render(): View
    {
        return view('operator::components.claimed-by');
    }
}
