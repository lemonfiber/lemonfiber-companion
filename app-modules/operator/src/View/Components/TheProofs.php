<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AProofAsShown;

use function view;

/**
 * Every proof, what it asks and establishes, and what it came to.
 *
 * One component for an install's proofs and a plugin's proofs asked again,
 * because it is the one verdict and two drawings of it would come to word a
 * proof not asked as one that passed.
 */
final class TheProofs extends Component
{
    /** @param list<AProofAsShown> $proofs */
    public function __construct(public readonly array $proofs) {}

    public function render(): View
    {
        return view('operator::components.the-proofs');
    }
}
