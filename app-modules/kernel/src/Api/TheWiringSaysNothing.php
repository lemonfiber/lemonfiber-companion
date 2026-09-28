<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What a wiring run came to arrived with a word it owes left blank.
 *
 * Refused rather than shown: a connection with no name, a failure without the
 * service's words, or a warning that will not say what breaks is one an
 * operator cannot act on.
 */
final class TheWiringSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a run reports many. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What a wiring run came to arrived with its `%s` blank, and an operator cannot act on a thing that will not say what it is.',
            $field,
        ));
    }

    /** A state that owes words was built without them, or one that carries none was handed some. */
    public static function wrongly(WhereAConnectionStands $state): self
    {
        return new self(sprintf(
            'A connection standing `%s` was built through a constructor that does not carry what that state says.',
            $state->value,
        ));
    }
}
