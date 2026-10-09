<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;

use function expect;

use Illuminate\Database\ConnectionInterface;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\NewestReading;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Requests\Internal\RequestsKept;
use Modules\Services\Internal\ListingsKept;
use Modules\Updates\Internal\UpkeepReadingsKept;
use Modules\Watching\Internal\LanguagesKept;

use function sprintf;

/**
 * What every store of readings promises, whichever capability's readings it keeps.
 *
 * Each store of readings has its own port, declared by the capability that
 * owns it, and every one makes the same promise over the same sealed
 * bookkeeping: one reading per stack with the later replacing the earlier, a
 * reading read exactly at the cut-off kept, and a row this build cannot read
 * said to be one. A fake that kept every reading, or pruned the one at the
 * cut-off, would make the owner's tests green about a phone that opens on the
 * wrong reading or forgets one it was meant to keep.
 *
 * So the promise is written once, here, and each port's contract runs it over
 * that port's adapter and fake. Every clause takes the stores it is run over
 * as makers, because the adapters stand on one database and migrating it for
 * the next would empty the last.
 */
final readonly class WhatAStoreOfReadingsPromises
{
    /** The moment the assertions are anchored at. */
    private const int WHEN_THE_READING_WAS_TAKEN = 1_780_000_000;

    /** A stack as a store names it: its keyed hash. */
    public static function aStack(): SealedStack
    {
        return SealedStack::of('a-keyed-hash-of-the-loft');
    }

    /** A second stack, whose reading must never answer for the first's. */
    public static function anotherStack(): SealedStack
    {
        return SealedStack::of('a-keyed-hash-of-the-shed');
    }

    /** A row for the first stack that a later build wrote, in a store's own table. */
    public static function aRowALaterBuildWroteIn(ConnectionInterface $database, string $table): void
    {
        $database->table($table)->insert([
            'stack_hash' => self::aStack()->forTheStore(),
            'shape' => 99,
            'read_at' => self::WHEN_THE_READING_WAS_TAKEN,
            'payload' => 'written-by-a-later-build',
        ]);
    }

    /** @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores */
    public static function findsNothingWhereNothingIsKept(array $stores): void
    {
        foreach ($stores as $which => $made) {
            expect(self::found($made()->newest(self::aStack())))->toBe('none', $which);
        }
    }

    /** @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores */
    public static function handsBackWhatItKeptAsItCame(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();

            expect(self::noted($store, self::aStack(), 'sealed-reading', 5))->toBe('down:5', $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('sealed-reading:1:5', $which);
        }
    }

    /** @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores */
    public static function keepsOneReadingPerStack(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();
            self::noted($store, self::aStack(), 'the-first-reading', 5);
            self::noted($store, self::aStack(), 'the-second-reading', 60);
            self::noted($store, self::anotherStack(), 'the-sheds-reading', 10);

            expect(self::found($store->newest(self::aStack())))->toBe('the-second-reading:1:60', $which)
                ->and(self::found($store->newest(self::anotherStack())))->toBe('the-sheds-reading:1:10', $which)
                ->and($store->forgetEverything()->howMany())->toBe(2, $which);
        }
    }

    /** @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores */
    public static function forgetsOneStackAndNoOther(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();
            self::noted($store, self::aStack(), 'the-lofts-reading', 5);
            self::noted($store, self::anotherStack(), 'the-sheds-reading', 10);

            expect($store->forget(self::aStack())->howMany())->toBe(1, $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('none', $which)
                ->and(self::found($store->newest(self::anotherStack())))->toBe('the-sheds-reading:1:10', $which)
                ->and($store->forget(self::aStack())->howMany())->toBe(0, $which);
        }
    }

    /**
     * The boundary is the decision: a reading exactly as old as the lifetime has not outlived it yet.
     *
     * @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept)> $stores
     */
    public static function forgetsWhatWasReadBeforeAMoment(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();
            self::noted($store, self::aStack(), 'read-before-the-moment', 99);
            self::noted($store, self::anotherStack(), 'read-at-the-moment', 100);

            expect($store->forgetOlderThan(self::secondsAfter(100))->howMany())->toBe(1, $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('none', $which)
                ->and(self::found($store->newest(self::anotherStack())))->toBe('read-at-the-moment:1:100', $which);
        }
    }

    /** @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores */
    public static function forgetsEverythingItKeeps(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();
            self::noted($store, self::aStack(), 'the-lofts-reading', 5);
            self::noted($store, self::anotherStack(), 'the-sheds-reading', 10);

            expect($store->forgetEverything()->howMany())->toBe(2, $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('none', $which)
                ->and(self::found($store->newest(self::anotherStack())))->toBe('none', $which)
                ->and($store->forgetEverything()->howMany())->toBe(0, $which);
        }
    }

    /**
     * Each store holding a row for the first stack that a later build wrote.
     *
     * @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores
     */
    public static function answersALaterBuildsRowAsUnreadable(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();

            expect(self::found($store->newest(self::aStack())))->toBe('unreadable', $which)
                ->and($store->newest(self::aStack())->holdsARow())->toBeTrue($which)
                ->and(self::found($store->newest(self::anotherStack())))->toBe('none', $which);

            self::noted($store, self::aStack(), 'written-by-this-build', 20);

            expect(self::found($store->newest(self::aStack())))->toBe('written-by-this-build:1:20', $which);
        }
    }

    /**
     * Each store holding a row for the first stack that a later build wrote.
     *
     * @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores
     */
    public static function forgetsAnUnreadableRowWithItsStack(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();

            expect($store->forget(self::aStack())->howMany())->toBe(1, $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('none', $which);
        }
    }

    /**
     * Each store over a database whose table is not there, which is the case an adapter can meet on a phone.
     *
     * @param array<string, Closure(): (HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept)> $stores
     */
    public static function answersEvenWhereItCannotBeReached(array $stores): void
    {
        foreach ($stores as $which => $made) {
            $store = $made();

            expect(self::noted($store, self::aStack(), 'sealed-reading', 5))->toBe('not-kept', $which)
                ->and(self::found($store->newest(self::aStack())))->toBe('none', $which)
                ->and($store->forget(self::aStack())->howMany())->toBe(0, $which)
                ->and($store->forgetEverything()->howMany())->toBe(0, $which);

            if ($store instanceof ForgetsOldReadings) {
                expect($store->forgetOlderThan(self::secondsAfter(100))->howMany())->toBe(0, $which);
            }
        }
    }

    /** A moment, counted in seconds from the one a reading was taken at. */
    private static function secondsAfter(int $seconds): Instant
    {
        return Instant::atEpochSeconds(self::WHEN_THE_READING_WAS_TAKEN + $seconds);
    }

    /** Which arm the newest reading answered on, and what it carried, as one word. */
    private static function found(NewestReading $newest): string
    {
        return $newest->either(
            found: static fn(SealedPayload $payload, Shape $shape, Instant $readAt): Code
                => Code::of(sprintf('%s:%d:%d', $payload->forTheStore(), $shape->value, $readAt->epochSeconds() - self::WHEN_THE_READING_WAS_TAKEN)),
            none: static fn(): Code => Code::of('none'),
            unreadable: static fn(): Code => Code::of('unreadable'),
        )->shown();
    }

    /** When a keep was noted as written, or that it was not, as one word. */
    private static function noted(HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept|LanguagesKept $store, SealedStack $stack, string $payload, int $seconds): string
    {
        return $store->keep($stack, SealedPayload::of($payload), Shape::One, self::secondsAfter($seconds))->either(
            down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - self::WHEN_THE_READING_WAS_TAKEN)),
            notKept: static fn(): Code => Code::of('not-kept'),
        )->shown();
    }
}
