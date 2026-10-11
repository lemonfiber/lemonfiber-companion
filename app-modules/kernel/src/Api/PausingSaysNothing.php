<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What pausing or resuming the downloads came to arrived with something it had to say left blank.
 *
 * Refused at construction, because a client with no name cannot be pointed at
 * and an offer with no name cannot be agreed to.
 */
final class PausingSaysNothing extends InvalidArgumentException
{
    /** A word the answer owes was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What pausing the downloads came to arrived with its `%s` blank, and a line that will not say what it is about cannot be acted on.',
            $field,
        ));
    }
}
