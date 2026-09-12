<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What a stack gives an operator once, to be traded for a session.
 *
 * `N1-R7` is two clauses and this type is built around the second. Exchanging a
 * credential for a session is ordinary; **not retaining it for re-sending** is
 * the part that takes a design, because retention is the default behaviour of
 * every value in every language. A `readonly` string held on a client object is
 * retained by definition, and the code that re-sends it on the next request is
 * one line somebody writes while fixing something else.
 *
 * So this is spent. {@see self::forTheExchange()} answers once and forgets, and
 * asks again are refused rather than answered. That makes "once" a fact about
 * the object rather than a promise about the caller.
 *
 * **Deliberately not `readonly`.** Every other value here is, and this one
 * cannot be: forgetting is a mutation, and it is the whole point. A readonly
 * credential would be one that still holds its secret after the exchange, which
 * is the thing `N1-R7` forbids — so the immutability that is right everywhere
 * else is exactly wrong here.
 *
 * **There is no `shown()`, and no `jsonSerialize()`.** `Session` has the second
 * because a session has to reach a header; a credential reaches one endpoint,
 * once, and nothing else ever needs to read it. `N1-R23` keeps it out of every
 * cache, and {@see MustNotLeaveThisProcess} is what refuses the writers that do
 * not ask.
 */
final class Credential
{
    private function __construct(private ?string $secret) {}

    /**
     * What a debugger prints.
     *
     * Says which of the two states it is in, because "is this spent" is the
     * question somebody has at a breakpoint, and it is the one question that can
     * be answered without printing the secret.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['secret' => $this->secret === null ? '(spent)' : '(a credential, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aCredential();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * The other half of the same door. Without it a crafted payload naming this
     * class would be walked back into an object holding whatever it carried —
     * including, in this case, an unspent credential nobody was given.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aCredential();
    }

    /**
     * The one place a string becomes a credential.
     *
     * Not trimmed beyond the blank check, for the reason `Session::of()` is not:
     * whitespace inside a credential is the stack's business, and a client that
     * quietly edits one before sending it fails in a way the server cannot
     * explain.
     */
    public static function of(string $secret): self
    {
        if (trim($secret) === '') {
            throw CredentialIsBlank::inPairingMaterial();
        }

        return new self($secret);
    }

    /**
     * The value, once, for the one exchange it exists for (`N1-R7`).
     *
     * Named for where it goes rather than for what it is, which is the same
     * argument `Session::forTheHeader()` makes: a general-purpose accessor makes
     * the rule a thing to remember, and an accessor naming its one destination
     * makes using it anywhere else read as obviously wrong.
     *
     * Forgets before answering rather than after. The order matters: an
     * exception thrown by anything downstream must not leave the secret still
     * held, and a caller that catches such an exception must not be able to ask
     * again.
     */
    public function forTheExchange(): string
    {
        $secret = $this->secret;

        if ($secret === null) {
            throw CredentialIsSpent::already();
        }

        $this->secret = null;

        return $secret;
    }

    /**
     * Whether this has been exchanged already.
     *
     * For a caller deciding whether it is holding something useful, not for one
     * deciding whether it is safe to read — {@see self::forTheExchange()}
     * refuses on its own, and a check-then-get pair is an invitation to call
     * the getter without the check.
     */
    public function wasSpent(): bool
    {
        return $this->secret === null;
    }
}
