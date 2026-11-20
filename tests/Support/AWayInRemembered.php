<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\RememberingTheWayIn;
use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\StackId;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\WaysInKeptInMemory;

/**
 * The way that last let a member in on each house, as a test arranges it.
 */
final readonly class AWayInRemembered
{
    /** When a way was noted in these tests. */
    private const int NOTED_AT = 1_790_000_060;

    /** A phone that remembers no way in for any house. */
    public static function nowhere(): RememberingTheWayIn
    {
        return new RememberingTheWayIn(ASealInMemory::working(), WaysInKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(self::NOTED_AT)));
    }

    /** A phone that remembers this way let a member in on this house. */
    public static function on(StackId $stack, TheWayIn $way): RememberingTheWayIn
    {
        $remembering = self::nowhere();
        $remembering->letIn($stack, $way);

        return $remembering;
    }
}
