<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Wayfinding\Api\Screens\FindsItsWayAroundAStack;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
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

    /**
     * Whether this screen ends with the way back to the machine's report.
     *
     * The operator's only, because the report is theirs. A member's session
     * opens no report to go back to, and the menu and the member's other
     * screen are their ways on.
     */
    public function goesBackToTheMachine(): bool
    {
        return ($this->menuIsFor ??= $this->around->whoTheMenuIsFor($this->stack())) === WhoTheMenuIsFor::TheOperator;
    }

    protected function opensOnTopOfAnother(): bool
    {
        return self::class !== WhatYouAreOwed::class;
    }
}
