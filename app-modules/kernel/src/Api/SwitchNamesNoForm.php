<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A switch that names no form, which would make nothing the active set.
 */
final class SwitchNamesNoForm extends InvalidArgumentException
{
    public static function atAll(): self
    {
        return new self('A switch was asked with no form chosen, and an active set of nothing is stopping the stack, which is its own verb.');
    }
}
