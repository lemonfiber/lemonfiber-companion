<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ACredentialRevokeAsShown;
use Modules\Operator\Internal\ViewModels\AnOffer;

use function view;

/**
 * Ending one grant, as the stack read it or as it came to: the grant, what restarts, that the plugin stays installed, and that ending it takes nothing back.
 */
final class TheRevoke extends Component
{
    public function __construct(public readonly ACredentialRevokeAsShown $revoke, public readonly AnOffer $offer) {}

    public function render(): View
    {
        return view('operator::components.the-revoke');
    }
}
