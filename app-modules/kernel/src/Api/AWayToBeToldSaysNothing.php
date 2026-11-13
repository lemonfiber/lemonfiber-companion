<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A way to be told arrived with no id to choose it by, or no name for the household to read. */
final class AWayToBeToldSaysNothing extends InvalidArgumentException
{
    public static function unnamed(): self
    {
        return new self('A way to be told arrived with no name, and a blank choice reads as something nobody offered.');
    }

    public static function unchoosable(): self
    {
        return new self('A way to be told arrived with no id, and a choice with no id cannot be sent back.');
    }
}
