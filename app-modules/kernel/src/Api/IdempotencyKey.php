<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What makes a command safe to send twice.
 *
 * This application sends commands over a link that drops. A phone loses wifi
 * mid-request and has no way to know whether the stack applied the update or
 * never heard the question. Without a key the safe answer is to do nothing and
 * ask the operator, which is the worst screen in the app; with one, retrying
 * is free, because the server recognises the second send as the first.
 *
 * Every command carries one in its constructor, which `M3` checks by
 * reflection rather than by review.
 *
 * The key is given rather than generated here. Randomness is an input and
 * arrives through the `Entropy` port (B2), and a key generated inside the
 * command would be a new one on every retry — which is precisely the thing it
 * exists to prevent.
 */
final readonly class IdempotencyKey
{
    private function __construct(private string $key) {}

    /**
     * What `serialize()` writes, which is nothing.
     *
     * The key is not a secret — it is sent in a header and anybody watching the
     * connection has it — so this is not the redaction `Session` does. It is the
     * other half of the requirement: a key serves retry *within a single
     * attempt*, and a serialised key is one that outlived its attempt. Whatever
     * reads it back sends the operator's earlier action again, at a moment
     * nobody chose, against a stack whose state has moved on.
     *
     * Retaining an undelivered action is already refused, and `Attempted`
     * has no arm for a pending one. This closes the same door from the other
     * side: even if a command were held, its key could not be written down.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::anIdempotencyKey();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * Without it a crafted payload naming this class walks back into an object
     * carrying whatever key it liked — and a key somebody else chose is a key
     * that matches an action the operator did not take.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::anIdempotencyKey();
    }

    /**
     * The one place a string becomes a key.
     *
     * Empty is refused. A blank key is one the server cannot recognise a
     * second send by, so a retry would apply the change twice — the failure
     * this type exists to make impossible, arriving quietly.
     */
    public static function of(string $key): self
    {
        $trimmed = trim($key);

        if ($trimmed === '') {
            throw KeyIsBlank::inACommand();
        }

        return new self($trimmed);
    }

    /**
     * A key from something nobody can guess, which is where one should come from.
     *
     * `of()` exists for a key the caller already holds — one read back from a
     * request that was interrupted, so the retry carries the same one. A key
     * being *made* comes from the `Entropy` port and through here, so that no
     * command has to decide for itself how long unguessable is (B2).
     */
    public static function from(Nonce $nonce): self
    {
        return new self($nonce->shown());
    }

    public function sent(): string
    {
        return $this->key;
    }

    public function is(self $other): bool
    {
        return $this->key === $other->key;
    }
}
