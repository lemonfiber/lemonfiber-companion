<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

final class ChannelIsUnnamed extends InvalidArgumentException
{
    public static function whereOneWasExpected(): self
    {
        return new self('A channel arrived with no identifier.');
    }
}
