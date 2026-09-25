<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A trace that arrived with something it owes left blank or impossible. */
final class TheTraceSaysNothing extends InvalidArgumentException
{
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A trace arrived with its `%s` blank or impossible, and a trace that will not say where an item got to answers nothing.',
            $field,
        ));
    }
}
