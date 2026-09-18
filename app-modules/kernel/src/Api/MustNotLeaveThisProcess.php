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
    /**
     * A key that was asked to outlive the attempt it belongs to.
     *
     * Different from the others above, and worth saying why it is here at all.
     * A key is not a secret — it goes on the wire in a header, and anybody
     * watching the connection has it. What it must not do is *persist*.
     *
     * `N1-R42` says a key serves retry within a single attempt and must not
     * replay an action across a reconnection. A serialised key is precisely a
     * key that outlived its attempt: whatever reads it back sends the operator's
     * earlier action again, at a moment nobody chose, against a stack whose
     * state has moved on. That is the failure `ADR-0020` spends its length
     * rejecting, arriving through the one door the ADR does not name.
     */
    public static function anIdempotencyKey(): self
    {
        return new self('An idempotency key was serialised. A key serves one attempt; one that is written down is one that replays the action after a reconnection (N1-R42).');
    }

    public static function aSession(): self
    {
        return new self('A session may not be serialised. It is a credential, and serialising one writes it in full wherever the result is kept — a cache entry, a queued payload, a session file. Pass the Session itself, or take the header off it at the edge.');
    }

    public static function anAddress(): self
    {
        return new self('A stack address may not be serialised. N1-R15 keeps it beside a credential, not because it is secret but because it is where somebody lives, and anything that serialises one accumulates a map of private networks.');
    }

    /**
     * A credential is spent once and is gone.
     *
     * The strictest of the three, because a credential that reached a cache is a
     * credential that can be replayed — and unlike a session, nothing on the
     * server side expires it on a schedule.
     */
    public static function aCredential(): self
    {
        // One literal, for the reason `CredentialIsSpent` carries: a
        // concatenated message is several mutants that no test can tell apart.
        return new self('A credential may not be serialised. N1-R7 exchanges it for a session once and keeps nothing to re-send, and N1-R23 keeps it out of every cache — a credential that reached one can be replayed, and unlike a session nothing on the server expires it on a schedule.');
    }
}
