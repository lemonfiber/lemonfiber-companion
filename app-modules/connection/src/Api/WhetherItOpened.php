<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

/**
 * What a fold over {@see \Modules\Kernel\Api\Lock} came to, as a fact.
 *
 * `Lock::either()` answers with an object, so a caller cannot learn whether the
 * device let them in without saying what happens both ways — which is right,
 * and leaves a caller that wants the plain answer nowhere to put it. This is
 * that somewhere, and it is deliberately the smallest thing that will do the
 * job: two named constructors and a field.
 *
 * The same shape {@see \Modules\Operator\Internal\WhetherItIsHeld} takes for
 * the keychain one module over, and kept separate for `E2`'s reason rather than
 * shared — a type named for what a lock did is readable where a type named for
 * either would be a second `Outcome` with worse names.
 */
final readonly class WhetherItOpened
{
    private function __construct(public bool $held) {}

    /** The device let the operator in. */
    public static function itDid(): self
    {
        return new self(held: false);
    }

    /** It did not, so the app stays shut and asks nothing of the network. */
    public static function itDidNot(): self
    {
        return new self(held: true);
    }
}
