<?php

declare(strict_types=1);

namespace Modules\Connection\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\WaysInKept;

/**
 * The way that last let a member in on each stack, in the phone's own database.
 */
final readonly class WaysInInTheDatabase implements WaysInKept
{
    use KeepsOneRowAStack;

    public const string TABLE = 'connection_way_in_kept';

    public function __construct(protected ConnectionInterface $database) {}
}
