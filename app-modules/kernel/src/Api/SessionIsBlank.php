<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stack admitted this app and sent nothing to carry.
 *
 * Thrown rather than returned, for the reason `CodeIsBlank` gives: this is a
 * value that cannot be constructed, raised at the one place a string becomes a
 * `Session`. There is nothing for a caller to handle — an empty token is not a
 * session that will work later.
 *
 * The message says nothing about what arrived, which is the one place in this
 * repository where a message is deliberately less useful than it could be. An
 * exception carrying a token puts it in a stack trace, and a stack trace is
 * exactly what ends up in a diagnostic report (`N1-R15`).
 */
final class SessionIsBlank extends InvalidArgumentException
{
    public static function fromTheStack(): self
    {
        return new self('A stack admitted this app and sent an empty session, which nothing can be carried in.');
    }
}
