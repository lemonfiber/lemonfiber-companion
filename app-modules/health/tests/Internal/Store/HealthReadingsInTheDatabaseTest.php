<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Internal\Store;

use function expect;
use function get_object_vars;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Schema;

use function it;

use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\TestCase;

use function uses;

// The application is booted here, unlike most module tests: the adapter is
// tested over the database the phone migrates, with the migration it ships.
uses(TestCase::class);

// What the contract cannot ask of the fake: the table the migration makes, and
// the row a reading is written as in it. How a row this class never writes is
// read is `store-kit`'s, and tested there.

/** The stack a row here belongs to, as the store names it. */
function theStackTheRowIsFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/**
 * The columns of the table the phone's migrations make, or nothing where they make none.
 *
 * A named function rather than the calls at the site, because the analyser
 * refuses a checked exception escaping a closure and every Pest body is one.
 *
 * @return array<mixed>
 */
function theColumnsThePhoneKeepsHealthIn(): array
{
    AKeptDatabase::migrated();

    return Schema::hasTable('health_readings') ? Schema::getColumnListing('health_readings') : [];
}

/**
 * Every row of the table, each as its columns.
 *
 * @return list<array<mixed>>
 */
function everyRowIn(ConnectionInterface $database): array
{
    $rows = [];

    foreach ($database->table('health_readings')->get() as $row) {
        $rows[] = get_object_vars($row);
    }

    return $rows;
}

it('is kept in a table the phone\'s own migrations make', function (): void {
    expect(theColumnsThePhoneKeepsHealthIn())->toEqualCanonicalizing(['stack_hash', 'shape', 'read_at', 'payload']);
});

it('writes a reading as the stack\'s hash, its shape, when it was read and the payload as it was sealed', function (): void {
    $database = AKeptDatabase::migrated();

    new HealthReadingsInTheDatabase($database)->keep(
        theStackTheRowIsFor(),
        SealedPayload::of('sealed-summary'),
        Shape::One,
        Instant::atEpochSeconds(1_790_000_000),
    );

    expect(everyRowIn($database))->toBe([[
        'stack_hash' => 'a-keyed-hash-of-the-loft',
        'shape' => 1,
        'read_at' => 1_790_000_000,
        'payload' => 'sealed-summary',
    ]]);
});
