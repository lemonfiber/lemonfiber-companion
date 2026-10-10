<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function count;

/**
 * Which step of the member's way in a phone is on: finding the house, then signing in, then Home.
 *
 * Home is the third step and is never drawn here: signing in lands on it.
 */
enum WhereTheWayInIs: int
{
    case FindingTheHouse = 1;

    case SigningIn = 2;

    /** Which step this is, counted from one. */
    public function step(): int
    {
        return $this->value;
    }

    /** How many steps there are: these, and Home after them. */
    public function ofHowMany(): int
    {
        return count(self::cases()) + 1;
    }
}
