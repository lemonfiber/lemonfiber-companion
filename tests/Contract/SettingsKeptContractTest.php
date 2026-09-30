<?php

declare(strict_types=1);

use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\Store\SettingsInTheDatabase;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ConnectionSettingsInMemory;

// The SettingsKept contract, run against the database store and the fake.
//
// The adapter runs over the app's own database in memory, with every migration
// the phone runs run over it, so the table it is tested against is the one its
// migration makes.

/** When a setting is set, in the assertions below. */
const WHEN_IT_WAS_SET = 1_790_000_000;

/** @return array<string, SettingsKept> */
function everySettingsStoreHoldingNothing(): array
{
    return [
        'the adapter' => new SettingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => ConnectionSettingsInMemory::empty(),
    ];
}

/** What a store holds, as one word. */
function whatTheSettingsStoreHolds(KeptSettings $kept): string
{
    return $kept->either(
        found: static fn(SealedPayload $payload, Shape $shape): Code => Code::of(sprintf('%s:%d', $payload->forTheStore(), $shape->value)),
        none: static fn(): Code => Code::of('none'),
    )->shown();
}

it('holds nothing before anything is kept', function (): void {
    foreach (everySettingsStoreHoldingNothing() as $which => $store) {
        expect(whatTheSettingsStoreHolds($store->kept()))->toBe('none', $which);
    }
});

it('keeps one value, the later in place of the earlier, and notes when', function (): void {
    foreach (everySettingsStoreHoldingNothing() as $which => $store) {
        $store->keep(SealedPayload::of('first'), Shape::One, Instant::atEpochSeconds(WHEN_IT_WAS_SET));
        $noted = $store->keep(SealedPayload::of('second'), Shape::One, Instant::atEpochSeconds(WHEN_IT_WAS_SET + 1));

        expect(whatTheSettingsStoreHolds($store->kept()))->toBe('second:1', $which)
            ->and($noted->either(
                down: static fn(Instant $at): Code => Code::of((string) $at->epochSeconds()),
                notKept: static fn(): Code => Code::of('not kept'),
            )->shown())->toBe((string) (WHEN_IT_WAS_SET + 1), $which);
    }
});

it('forgets everything, and says how much', function (): void {
    foreach (everySettingsStoreHoldingNothing() as $which => $store) {
        $store->keep(SealedPayload::of('kept'), Shape::One, Instant::atEpochSeconds(WHEN_IT_WAS_SET));

        expect($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and(whatTheSettingsStoreHolds($store->kept()))->toBe('none', $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('answers a store that cannot be reached as one that kept nothing', function (): void {
    foreach ([
        'the adapter' => new SettingsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => ConnectionSettingsInMemory::unreachable(),
    ] as $which => $store) {
        expect($store->keep(SealedPayload::of('kept'), Shape::One, Instant::atEpochSeconds(WHEN_IT_WAS_SET))->either(
            down: static fn(): Code => Code::of('down'),
            notKept: static fn(): Code => Code::of('not kept'),
        )->shown())->toBe('not kept', $which)
            ->and(whatTheSettingsStoreHolds($store->kept()))->toBe('none', $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('answers a row in a shape this build does not know as none', function (): void {
    $database = AKeptDatabase::migrated();
    $database->table('connection_settings')->insert(['row' => 1, 'shape' => 99, 'set_at' => WHEN_IT_WAS_SET, 'payload' => 'kept']);

    expect(whatTheSettingsStoreHolds(new SettingsInTheDatabase($database)->kept()))->toBe('none');
});
