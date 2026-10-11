<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A session the media server is playing arrived with a word it owes left blank.
 *
 * Refused at construction, because a session that names nobody, nothing or no
 * device tells the operator nothing they could act on.
 */
final class SessionPlayingSaysNothing extends InvalidArgumentException
{
    /** A word the session owes was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf('A session the media server is playing arrived with its `%s` blank, and cannot be shown.', $field));
    }
}
