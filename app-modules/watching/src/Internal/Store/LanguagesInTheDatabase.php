<?php

declare(strict_types=1);

namespace Modules\Watching\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Modules\StoreKit\Api\AnswersFromItsTable;
use Modules\StoreKit\Api\ATableOfReadings;
use Modules\Watching\Internal\LanguagesKept;

/**
 * {@see LanguagesKept}, answered by the app's own database.
 *
 * **One table, `watching_languages`, and this is the one class that names
 * it.** It is created by this module's own migration, and no other module
 * reads it. Every query is `store-kit`'s, over that table.
 */
final readonly class LanguagesInTheDatabase implements LanguagesKept
{
    use AnswersFromItsTable;

    /** The table this module owns. */
    private const string TABLE = 'watching_languages';

    public function __construct(private ConnectionInterface $database) {}

    protected function table(): ATableOfReadings
    {
        return new ATableOfReadings($this->database, self::TABLE);
    }
}
