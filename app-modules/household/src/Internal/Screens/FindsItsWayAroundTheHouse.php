<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Wayfinding\Api\Screens\FindsItsWayAroundAStack;
use Native\Mobile\Edge\NativeComponent;

/**
 * The way around, on a member's screen about a stack.
 *
 * What a member is owed is where they land on a stack, so it is drawn as the
 * root the way an operator's tab is; what they can watch is opened on top of
 * it and has a back button.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FindsItsWayAroundTheHouse
{
    use FindsItsWayAroundAStack;

    protected function opensOnTopOfAnother(): bool
    {
        return self::class !== WhatYouAreOwed::class;
    }
}
