<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A member's name that names nobody, which the door would answer as the operator's password alone. */
final class NobodyWasNamed extends InvalidArgumentException
{
    public static function atTheDoor(): self
    {
        return new self('A sign-in was offered as a member with no name, and a door offered no name tries the machine\'s own password only.');
    }
}
