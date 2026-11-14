<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** Where alerts go arrived with a part blank that an operator has to be able to read. */
final class WhereAlertsGoSaysNothing extends InvalidArgumentException
{
    public static function about(string $part): self
    {
        return new self(sprintf('Where alerts go arrived with %s blank, and a blank there reads as something the stack never said.', $part));
    }
}
