<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Kernel\Api\HowFast;
use Modules\Kernel\Api\RateUnit;

use function sprintf;

/** A speed as it reaches a screen: the figure and the unit it is said in (`G10`). */
function asARate(int $bytesASecond): string
{
    $shown = HowFast::of($bytesASecond);

    return sprintf('%d %s', $shown->figure, $shown->said);
}

/**
 * Every band that has one above it, derived rather than listed, for
 * `HowBigTest`'s reason.
 *
 * @return list<RateUnit>
 */
function everyRateWithOneAbove(): array
{
    return array_values(array_filter(
        RateUnit::cases(),
        static fn(RateUnit $unit): bool => $unit->next() !== $unit,
    ));
}

it('N10-R4 — says a line in bits a second, in the largest unit it fills', function (): void {
    // 12.5 MB a second is the 100 Mbit/s line it was sold as, and 250 kB a
    // second is a 2 Mbit/s upload — which a scale starting at megabytes draws
    // as nought.
    expect(asARate(12_500_000))->toBe('100 stacks.line.rate.megabits')
        ->and(asARate(250_000))->toBe('2 stacks.line.rate.megabits')
        ->and(asARate(62_500))->toBe('500 stacks.line.rate.kilobits')
        ->and(asARate(125_000_000))->toBe('1 stacks.line.rate.gigabits');
});

it('says a line that carries nothing as nought of the smallest unit', function (): void {
    expect(asARate(0))->toBe('0 stacks.line.rate.kilobits');
});

it('chooses the band by the raw figure and rounds rather than floors', function (): void {
    // 600 Mbit/s stays itself rather than becoming `1 Gbit/s`, and 1.9 Gbit/s
    // is `2`, not the `1` flooring would understate it as.
    expect(asARate(75_000_000))->toBe('600 stacks.line.rate.megabits')
        ->and(asARate(237_500_000))->toBe('2 stacks.line.rate.gigabits')
        ->and(asARate(175_000_000))->toBe('1 stacks.line.rate.gigabits');
});

it('moves up a band exactly where staying would need four figures', function (RateUnit $unit): void {
    // The last figure a band keeps, and the first that belongs to the next.
    $lastByte = (int) ($unit->bits() * 999 / 8);
    $firstByte = (int) ($unit->bits() * 1000 / 8);

    expect(asARate($lastByte))->toBe(sprintf('999 %s', $unit->saidOnTheScreen()))
        ->and(asARate($firstByte))->toBe(sprintf('1 %s', $unit->next()->saidOnTheScreen()));
})->with(everyRateWithOneAbove());

it('lets the largest band answer itself', function (): void {
    expect(RateUnit::Gigabits->next())->toBe(RateUnit::Gigabits)
        ->and(asARate(1_250_000_000))->toBe('10 stacks.line.rate.gigabits');
});
