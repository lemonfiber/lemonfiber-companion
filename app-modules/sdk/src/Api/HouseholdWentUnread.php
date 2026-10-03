<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use RuntimeException;

/**
 * The stack answered that it could not read the household from its media server.
 *
 * Its own exception rather than a {@see HouseholdIsUnreadable}, which is an
 * answer this app cannot read: this one was read, and says why its list of
 * people is empty.
 */
final class HouseholdWentUnread extends RuntimeException
{
    public static function atTheMediaServer(): self
    {
        return new self(
            'The stack says it could not read the household, so its list is empty for a reason that is not an empty house. Reading it as one would tell somebody there is nothing waiting when the truth is that nobody could find out.',
        );
    }
}
