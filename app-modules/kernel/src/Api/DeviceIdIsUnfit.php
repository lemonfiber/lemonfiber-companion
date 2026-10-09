<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** An id for this device that the core would not accept. */
final class DeviceIdIsUnfit extends InvalidArgumentException
{
    public static function forTheCore(): self
    {
        return new self('A device is named with eight to sixty-four letters, digits and hyphens, and this id is not.');
    }
}
