<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A setting was named to be revealed in a support bundle, and the name was blank.
 *
 * Refused where the text becomes an {@see ASettingToReveal}: a blank name
 * reveals nothing the stack could find, and sent it would be a request to
 * publish a value nobody chose.
 */
final class ASettingHasNoName extends InvalidArgumentException
{
    public static function toReveal(): self
    {
        return new self('A setting to reveal in a support bundle has to be named. A blank name names no setting.');
    }
}
