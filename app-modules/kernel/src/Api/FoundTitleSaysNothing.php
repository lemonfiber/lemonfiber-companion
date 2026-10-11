<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A title a search found, or what asking for one came to, arrived with something it owes left blank.
 *
 * Refused at construction, because a title with no id cannot be asked for and
 * one with no name cannot be shown.
 */
final class FoundTitleSaysNothing extends InvalidArgumentException
{
    /** A word the reading owes was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf('A title a member found arrived with its `%s` blank, and it can be neither shown nor asked for.', $field));
    }
}
