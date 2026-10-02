<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\WhatIsNotHereYet;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * A screen this version of the app does not have yet, which says so.
 *
 * Opened from the menu's What's new and its two settings, titled by the item
 * that opened it.
 */
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class NotInThisVersionYet extends NativeComponent
{
    /** Which item opened it, read off its route; one it does not know is titled as What's new. */
    public function which(): WhatIsNotHereYet
    {
        $named = $this->param('what');

        return WhatIsNotHereYet::tryFrom(is_string($named) ? $named : '') ?? WhatIsNotHereYet::WhatsNew;
    }

    /** Where the list of stacks is, which is the way on from here. */
    public function theListIsAt(): string
    {
        return AScreenWithoutAStack::TheList->value;
    }

    /** The frame, by name. */
    public function render(): View
    {
        return view('operator::not-in-this-version-yet');
    }
}
