<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Instant;

/**
 * A clock that answers what the test told it to.
 *
 * Hand-written rather than mocked, which is what `G1` asks for and what makes
 * `G2` worth having: a mock encodes a guess about behaviour and keeps passing
 * after the real thing changes, where a fake held to the same contract test as
 * the adapter cannot drift without the run saying so.
 *
 * It lives in the root test support rather than in a module, so that no fake
 * is reachable from production code at all (`G4`). Every module test and every
 * contract suite can name it, because they are all dev paths.
 *
 * Mutable on purpose, which is the one place this codebase permits it: a test
 * about a session expiring has to move time between two calls, and a clock
 * that cannot be moved would force it to build a second subject halfway
 * through and assert on something it did not set up.
 */
final class FrozenClock implements Clock
{
    private function __construct(private Instant $now) {}

    public static function at(Instant $now): self
    {
        return new self($now);
    }

    public function now(): Instant
    {
        return $this->now;
    }

    /** Move the clock, for a test about something that happens later. */
    public function moveTo(Instant $now): void
    {
        $this->now = $now;
    }
}
