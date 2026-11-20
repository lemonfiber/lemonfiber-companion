<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Connection\Internal\WakingKept;

/**
 * Whether each stack wakes the phone, kept in memory, for a test to arrange and read.
 */
final class WakingKeptInMemory implements WakingKept
{
    use KeepsOneRowAStackInMemory;
}
