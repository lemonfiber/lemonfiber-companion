<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A reading of the running copy of lemonfiber that arrived with a word it owes left blank. */
final class ItselfSaysNothing extends InvalidArgumentException
{
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A reading of the running copy of lemonfiber arrived with its `%s` blank, and a copy that will not say what it is cannot be weighed.',
            $field,
        ));
    }
}
