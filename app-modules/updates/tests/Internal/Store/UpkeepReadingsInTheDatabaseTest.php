<?php

declare(strict_types=1);

namespace Modules\Updates\Tests\Internal\Store;

use function expect;
use function get_object_vars;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Schema;

use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Tests\Support\AKeptDatabase;
use Tests\TestCase;

use function uses;

// The application is booted here, unlike most module tests: the adapter is
// tested over the database the phone migrates, with the migration it ships.
uses(TestCase::class);

// What the contract cannot ask of the fake: the table the migration makes, the
// row a reading is written as, and a row holding what this class never writes.

/** The stack a row here belongs to, as the store names it. */
function theStackTheUpkeepRowIsFor(): SealedStack
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
function theColumnsThePhoneKeepsUpkeepIn(): array
{
    AKeptDatabase::migrated();

    return Schema::hasTable('updates_readings') ? Schema::getColumnListing('updates_readings') : [];
}

/**
 * Every row of the table, each as its columns.
 *
 * @return list<array<mixed>>
 */
function everyUpkeepRowIn(ConnectionInterface $database): array
{
    $rows = [];

    foreach ($database->table('updates_readings')->get() as $row) {
        $rows[] = get_object_vars($row);
    }

    return $rows;
}

it('is kept in a table the phone\'s own migrations make', function (): void {
    expect(theColumnsThePhoneKeepsUpkeepIn())->toEqualCanonicalizing(['stack_hash', 'shape', 'read_at', 'payload']);
});

it('writes a reading as the stack\'s hash, its shape, when it was read and the payload as it was sealed', function (): void {
    $database = AKeptDatabase::migrated();

    new UpkeepReadingsInTheDatabase($database)->keep(
        theStackTheUpkeepRowIsFor(),
        SealedPayload::of('sealed-reading'),
        Shape::One,
        Instant::atEpochSeconds(1_790_000_000),
    );

    expect(everyUpkeepRowIn($database))->toBe([[
        'stack_hash' => 'a-keyed-hash-of-the-loft',
        'shape' => 1,
        'read_at' => 1_790_000_000,
        'payload' => 'sealed-reading',
    ]]);
});

it('answers a row holding what it never writes as a reading it cannot read', function (string $column, int|string $holding): void {
    $database = AKeptDatabase::migrated();

    $database->table('updates_readings')->insert([
        ...[
            'stack_hash' => theStackTheUpkeepRowIsFor()->forTheStore(),
            'shape' => 1,
            'read_at' => 1_790_000_000,
            'payload' => 'sealed-reading',
        ],
        $column => $holding,
    ]);

    expect(new UpkeepReadingsInTheDatabase($database)->newest(theStackTheUpkeepRowIsFor())->either(
        found: static fn(): Code => Code::of('found'),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown())->toBe('unreadable');
})->with([
    'a shape no build writes' => ['shape', 99],
    'a shape that is not a number' => ['shape', 'one'],
    'a moment that is not a number' => ['read_at', 'yesterday'],
    'a payload that is not text' => ['payload', 7],
]);
