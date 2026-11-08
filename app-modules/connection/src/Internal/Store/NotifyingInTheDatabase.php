<?php

declare(strict_types=1);

namespace Modules\Connection\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\NotifyingKept;

/**
 * {@see NotifyingKept} over the app's own database: one table, `connection_notifying_kept`, one row a stack.
 *
 * A database that refuses is answered as nothing kept and nothing forgotten:
 * a phone that cannot keep the choices tells everything the stack raises,
 * which is what it does until the operator chooses.
 */
final readonly class NotifyingInTheDatabase implements NotifyingKept
{
    use KeepsOneRowAStack;
    private const string TABLE = 'connection_notifying_kept';

    public function __construct(protected ConnectionInterface $database) {}
}
