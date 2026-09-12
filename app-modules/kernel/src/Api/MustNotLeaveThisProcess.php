<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * Something `N1-R15` keeps inside the process was asked to leave it.
 *
 * Thrown from `__serialize()`, which is the third way a value gets out and the
 * one the other two do not cover. `__debugInfo()` answers `var_dump` and
 * `print_r`; `jsonSerialize()` answers `json_encode`; and `serialize()` walks
 * private properties itself and asks neither. A session token or a stack
 * address reaches a cache entry, a queued job payload or a session file that
 * way, in full, with nothing in the type to stop it.
 *
 * **It refuses rather than redacting.** Redacting is what the other two do,
 * because their readers are people: a debugger line that says
 * `(a session, hidden)` has told the reader everything true that it can. A
 * `serialize()` reader is code, and code that unserialises
 * `(a session, hidden)` gets a `Session` object that is a perfectly valid type
 * carrying a string that is not a token — which is then sent as a header, and
 * fails at the far end as an authentication error about a session that never
 * existed. The refusal is the only answer that cannot be mistaken for the value.
 *
 * A `LogicException` rather than a runtime one, and that is the claim: this is
 * not a condition to handle. Code that serialises one of these types is code
 * asking for something the design does not have, and the fix is at the call
 * site every time.
 */
final class MustNotLeaveThisProcess extends LogicException
{
    public static function aSession(): self
    {
        return new self(
            'A session may not be serialised. It is a credential, and serialising one writes it '
            . 'in full wherever the result is kept — a cache entry, a queued payload, a session '
            . 'file. Pass the Session itself, or take the header off it at the edge.',
        );
    }

    public static function anAddress(): self
    {
        return new self(
            'A stack address may not be serialised. N1-R15 keeps it beside a credential, not '
            . 'because it is secret but because it is where somebody lives, and anything that '
            . 'serialises one accumulates a map of private networks.',
        );
    }
}
