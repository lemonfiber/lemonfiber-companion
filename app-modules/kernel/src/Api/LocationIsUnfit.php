<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A location the household's door would not serve. */
final class LocationIsUnfit extends InvalidArgumentException
{
    public static function notAtTheDoor(): self
    {
        return new self('A location at the household\'s door is served over https, and this one is not.');
    }
}
