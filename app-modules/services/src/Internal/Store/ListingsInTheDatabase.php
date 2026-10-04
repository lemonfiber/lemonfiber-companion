<?php

declare(strict_types=1);

namespace Modules\Services\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\Services\Internal\ListingsKept;
use Modules\StoreKit\Api\AnswersFromItsTable;
use Modules\StoreKit\Api\ATableOfReadings;

/**
 * {@see ListingsKept}, answered by the app's own database.
 *
 * **One table, `services_readings`, and this is the one class that names it.**
 * It is created by this module's own migration, and no other module reads it:
 * one that wants what a stack runs asks `services`.
 *
 * Every query is `store-kit`'s, over that table: this class says which table
 * is this module's and nothing else.
 */
final readonly class ListingsInTheDatabase implements ListingsKept
{
    use AnswersFromItsTable;

    /** The table this module owns. */
    private const string TABLE = 'services_readings';

    public function __construct(private ConnectionInterface $database) {}

    private function table(): ATableOfReadings
    {
        return new ATableOfReadings($this->database, self::TABLE);
    }
}
