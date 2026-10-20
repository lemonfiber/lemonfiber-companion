<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A setting or a secret arrived with a word it owes left blank.
 */
final class SettingSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because there are several. */
    public static function about(string $field): self
    {
        return new self(sprintf('A setting arrived with its `%s` blank, and a setting nobody can name is not one to change.', $field));
    }
}
