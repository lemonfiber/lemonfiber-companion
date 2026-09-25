<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A reading of the household's front door arrived with a word it owes left blank.
 *
 * Refused rather than shown: an address with nothing in it is one somebody
 * would be sent to and find nothing at.
 */
final class TheDoorSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because the list can be long. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A reading of the front door arrived with its `%s` blank, and a door that will not say it is not one to send somebody to.',
            $field,
        ));
    }
}
