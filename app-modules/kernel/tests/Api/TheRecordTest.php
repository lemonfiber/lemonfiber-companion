<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\TheRecordHasNoHorizon;
use Modules\Kernel\Api\WhenItWasMade;

/** A change named for what it did, so an order can be read off a record. */
function aChangeThatDid(string $did, int $at = 1_790_142_840): Change
{
    return Change::made($did, 'reconfigure', 'sonarr', WhenItWasMade::at(Instant::atEpochSeconds($at)), HowFarItGoesBack::Whole, 1);
}

/**
 * Every change in a record, by what it did, in the order it holds them.
 *
 * @return list<string>
 */
function whatTheRecordSaysWasDone(TheRecord $record): array
{
    $done = [];

    foreach ($record as $change) {
        $done[] = $change->did();
    }

    return $done;
}

it('N11-R1 — carries how far back it goes', function (): void {
    expect(TheRecord::reaching('The last 90 days')->horizon())->toBe('The last 90 days');
});

it('N11-R1 — refuses a record that will not say how far back it goes', function (): void {
    // Its oldest entry would read as the machine's first day rather than as
    // the end of what is kept.
    expect(fn(): TheRecord => TheRecord::reaching('   '))->toThrow(TheRecordHasNoHorizon::class);
});

it('N11-R9 — a record of nothing is still a record', function (): void {
    // The stack answered and has changed nothing. Told apart from a stack
    // that could not be asked by being a value at all.
    $record = TheRecord::reaching('The last 90 days');

    expect($record->count())->toBe(0)
        ->and(whatTheRecordSaysWasDone($record))->toBe([]);
});

it('N11-R10 — keeps the order the stack gave, rather than re-deciding it', function (): void {
    // Two at the same instant stay in the order they arrived. Sorting them
    // here would be an opinion about which came first, which is exactly what
    // the requirement refuses.
    $record = TheRecord::reaching(
        'The last 90 days',
        aChangeThatDid('Third', 1_790_142_840),
        aChangeThatDid('Second', 1_790_142_840),
        aChangeThatDid('First', 1_790_110_000),
    );

    expect(whatTheRecordSaysWasDone($record))->toBe(['Third', 'Second', 'First'])
        ->and($record->count())->toBe(3);
});

it('is a list rather than whatever keys a variadic brought', function (): void {
    // Named arguments, which is the one call shape where the reindex changes
    // the value — positionally a variadic is already a list, and this case
    // would pass with the reindex and without it.
    $record = TheRecord::reaching(...[
        'horizon' => 'The last 90 days',
        'first' => aChangeThatDid('Third'),
        'second' => aChangeThatDid('Second'),
    ]);

    expect(array_keys(iterator_to_array($record, preserve_keys: true)))->toBe([0, 1]);
});
