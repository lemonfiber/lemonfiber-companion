<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A machine's manager came with a caveat that says nothing.
 */
final class CaveatSaysNothing extends InvalidArgumentException
{
    public static function aboutTheManager(): self
    {
        return new self('A service manager came with a caveat that says nothing, and a blank under the manager reads as nothing worth knowing.');
    }
}
