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

    public function sent(): string
    {
        return $this->key;
    }

    public function is(self $other): bool
    {
        return $this->key === $other->key;
    }
}
