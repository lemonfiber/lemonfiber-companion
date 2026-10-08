<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Modules\Operator\View\HowAnOfferIsDrawn;

use function view;

/**
 * A button for something the stack is asked to do, drawn as the stack offers it.
 *
 * Pressable where the stack offers it, and drawn and explained where it does
 * not: a stack too old for it says so with a road to its updates, and an
 * account it is not for says that, rather than the button being taken away.
 * One component for every such button, so no screen works the answer out for
 * itself.
 */
final class OfferedAction extends Component
{
    public readonly HowAnOfferIsDrawn $look;

    public readonly bool $pressable;

    public function __construct(
        public readonly string $label,
        public readonly string $tap,
        public readonly AnOffer $offer,
        public readonly string $tone = 'primary',
        public readonly string $answersTo = '',
        string $drawn = 'button',
        bool $disabled = false,
    ) {
        $this->look = HowAnOfferIsDrawn::from($drawn);
        $this->pressable = $offer->offers && ! $disabled;
    }

    public function render(): View
    {
        return view('operator::components.offered-action');
    }
}
