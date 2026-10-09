<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A grant the door would not accept. */
final class GrantIsUnfit extends InvalidArgumentException
{
    public static function fromTheStack(): self
    {
        return new self('A stack answered a grant that is not thirty-two lowercase hexadecimal digits, which the door does not accept.');
    }
}
