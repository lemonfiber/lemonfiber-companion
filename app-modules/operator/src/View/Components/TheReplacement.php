<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\AReplacementAsShown;
use Modules\Operator\Internal\ViewModels\WhoIsWatchingAsShown;

use function view;

/**
 * Replacing the internal certificate, as the stack read it or as it came to: what it would do and who a restart interrupts, or where it settled and how far it reached each holder, and that the phone's pairing is untouched.
 */
final class TheReplacement extends Component
{
    public function __construct(
        public readonly AReplacementAsShown $replacement,
        public readonly AnOffer $offer,
        public readonly ?WhoIsWatchingAsShown $watching = null,
        public readonly ?ARefusalAsShown $movedOn = null,
    ) {}

    public function render(): View
    {
        return view('operator::components.the-replacement');
    }
}
