<?php

declare(strict_types=1);

namespace Modules\Connection\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\WakingKept;

/**
 * {@see WakingKept} over the app's own database: one table, `connection_waking_kept`, one row a stack.
 *
 * A database that refuses is answered as nothing kept and nothing forgotten:
 * a phone that cannot keep that a stack wakes it is not woken by that stack.
 */
final readonly class WakingInTheDatabase implements WakingKept
{
    use KeepsOneRowAStack;
    public const string TABLE = 'connection_waking_kept';

    public function __construct(protected ConnectionInterface $database) {}
}
