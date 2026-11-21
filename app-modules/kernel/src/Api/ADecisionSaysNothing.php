<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A decision setup makes arrived with one of its parts empty. */
final class ADecisionSaysNothing extends InvalidArgumentException
{
    public static function about(string $part): self
    {
        return new self(sprintf('a decision setup makes arrived with no %s', $part));
    }
}
