<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Household\Internal\Screens\FindsItsWayAroundTheHouse;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Edge\NativeComponent;

/** A member's screen about a house that no tab draws, as a title's page opened over Home will be. */
final class AMemberScreenOpenedOverATab extends NativeComponent
{
    use FindsItsWayAroundTheHouse;

    public function __construct(private readonly TheWayAround $around) {}
}
