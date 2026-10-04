<?php

declare(strict_types=1);

namespace Modules\Updates\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\StoreKit\Api\AnswersFromItsTable;
use Modules\StoreKit\Api\ATableOfReadings;
use Modules\Updates\Internal\UpkeepReadingsKept;

/**
 * {@see UpkeepReadingsKept}, answered by the app's own database.
 *
 * **One table, `updates_readings`, and this is the one class that names it.**
 * It is created by this module's own migration, and no other module reads it:
 * one that wants where a stack stands on being up to date asks `updates`.
 *
 * Every query is `store-kit`'s, over that table: this class says which table
 * is this module's and nothing else.
 */
final readonly class UpkeepReadingsInTheDatabase implements UpkeepReadingsKept
{
    use AnswersFromItsTable;

    /** The table this module owns. */
    private const string TABLE = 'updates_readings';

    public function __construct(private ConnectionInterface $database) {}

    protected function table(): ATableOfReadings
    {
        return new ATableOfReadings($this->database, self::TABLE);
    }
}
