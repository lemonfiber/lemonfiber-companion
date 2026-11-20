<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Connection\Internal\WaysInKept;

/**
 * The way that last let a member in on each stack, kept in memory, for a test to arrange and read.
 */
final class WaysInKeptInMemory implements WaysInKept
{
    use KeepsOneRowAStackInMemory;
}
