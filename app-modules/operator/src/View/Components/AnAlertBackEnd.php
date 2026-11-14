<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnAlertBackEndAsShown;

use function view;

/** One back-end alerts go to, as a card, opening its own page where `opens` says. */
final class AnAlertBackEnd extends Component
{
    public function __construct(
        public readonly AnAlertBackEndAsShown $backEnd,
        public readonly bool $opens = true,
    ) {}

    public function render(): View
    {
        return view('operator::components.an-alert-back-end');
    }
}
