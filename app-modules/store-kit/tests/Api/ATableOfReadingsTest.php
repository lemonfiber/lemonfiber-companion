<?php

declare(strict_types=1);

namespace Modules\StoreKit\Tests\Api;

use function expect;
use function get_object_vars;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\StoreKit\Api\ATableOfReadings;
use Tests\Support\AKeptDatabase;
use Tests\TestCase;

use function uses;

// The application is booted here, unlike most module tests: the queries are
// tested over a real database, in a table laid out as every owner's migration
// lays out its own.
uses(TestCase::class);

// What the store contracts cannot ask of a fake: the row a reading is written
// as, and a row holding what this class never writes. Each owner's store is
// held to the rest by its contract, over its own table.

/** The table an owner would name, made here as an owner's migration makes one. */
const THE_TABLE_AN_OWNER_NAMES = 'an_owners_readings';

/** The stack a row here belongs to, as the store names it. */
function theStackTheKitsRowIsFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** A database holding one owner's table, empty, with the four columns every owner's migration makes. */
function aDatabaseWithAnOwnersTable(): ConnectionInterface
{
    $database = AKeptDatabase::empty();

    Schema::create(THE_TABLE_AN_OWNER_NAMES, static function (Blueprint $table): void {
        $table->string('stack_hash')->primary();
        $table->unsignedInteger('shape');
        $table->unsignedBigInteger('read_at')->index();
        $table->binary('payload');
    });

    return $database;
}

/**
 * Every row of the table, each as its columns.
 *
 * @return list<array<mixed>>
 */
function everyRowOfTheOwnersTable(ConnectionInterface $database): array
{
    $rows = [];

    foreach ($database->table(THE_TABLE_AN_OWNER_NAMES)->get() as $row) {
        $rows[] = get_object_vars($row);
    }

    return $rows;
}

it('writes a reading in the table it is handed, as the stack\'s hash, its shape, when it was read and the payload as it was sealed', function (): void {
    $database = aDatabaseWithAnOwnersTable();

    new ATableOfReadings($database, THE_TABLE_AN_OWNER_NAMES)->keep(
        theStackTheKitsRowIsFor(),
        SealedPayload::of('sealed-reading'),
        Shape::One,
        Instant::atEpochSeconds(1_790_000_000),
    );

    expect(everyRowOfTheOwnersTable($database))->toBe([[
        'stack_hash' => 'a-keyed-hash-of-the-loft',
        'shape' => 1,
        'read_at' => 1_790_000_000,
        'payload' => 'sealed-reading',
    ]]);
});

it('answers a row holding what it never writes as a reading it cannot read', function (string $column, int|string $holding): void {
    $database = aDatabaseWithAnOwnersTable();

    $database->table(THE_TABLE_AN_OWNER_NAMES)->insert([
        ...[
            'stack_hash' => theStackTheKitsRowIsFor()->forTheStore(),
            'shape' => 1,
            'read_at' => 1_790_000_000,
            'payload' => 'sealed-reading',
        ],
        $column => $holding,
    ]);

    expect(new ATableOfReadings($database, THE_TABLE_AN_OWNER_NAMES)->newest(theStackTheKitsRowIsFor())->either(
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
