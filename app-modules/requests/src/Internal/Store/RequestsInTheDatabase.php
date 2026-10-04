<?php

declare(strict_types=1);

namespace Modules\Requests\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\Requests\Internal\RequestsKept;
use Modules\StoreKit\Api\AnswersFromItsTable;
use Modules\StoreKit\Api\ATableOfReadings;

/**
 * {@see RequestsKept}, answered by the app's own database.
 *
 * **One table, `requests_readings`, and this is the one class that names it.**
 * It is created by this module's own migration, and no other module reads it:
 * one that wants what a household asked for asks `requests`.
 *
 * Every query is `store-kit`'s, over that table: this class says which table
 * is this module's and nothing else.
 */
final readonly class RequestsInTheDatabase implements RequestsKept
{
    use AnswersFromItsTable;

    /** The table this module owns. */
    private const string TABLE = 'requests_readings';

    public function __construct(private ConnectionInterface $database) {}

    protected function table(): ATableOfReadings
    {
        return new ATableOfReadings($this->database, self::TABLE);
    }
}
