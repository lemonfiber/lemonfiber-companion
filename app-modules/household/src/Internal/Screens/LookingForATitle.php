<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Edge\NativeComponent;

/**
 * The member's Search tab.
 *
 * The house cannot yet be searched from the phone, so the tab says so in
 * household words and points to where what the member can watch, and what
 * they asked for, already are. It asks the house nothing.
 */
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class LookingForATitle extends NativeComponent
{
    use FindsItsWayAroundTheHouse;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::looking-for-a-title';

    public function __construct(protected readonly TheWayAround $around) {}
}
