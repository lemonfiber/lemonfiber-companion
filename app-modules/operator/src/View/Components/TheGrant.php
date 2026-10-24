<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ViewModels\AGrantAsShown;
use Modules\Operator\Internal\ViewModels\AnOffer;

use function view;

/**
 * One grant of a credential to a third-party plugin: who it is granted to, what for and since when, and ending it where that is offered.
 *
 * One component for the credentials screen and a plugin's own list, because a
 * grant drawn two ways would come to say two things.
 */
final class TheGrant extends Component
{
    use WritesItsTaps;

    /** @param AnOffer|null $offer what ending it is offered as, or none where it is listed only */
    public function __construct(public readonly AGrantAsShown $grant, public readonly ?AnOffer $offer = null) {}

    public function render(): View
    {
        return view('operator::components.the-grant');
    }
}
