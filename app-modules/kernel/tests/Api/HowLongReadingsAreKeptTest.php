<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeptFor;

/** How long, as one word. */
function howLongThatIs(HowLongReadingsAreKept $kept): string
{
    return $kept->either(
        days: static fn(int $days): Code => Code::of((string) $days),
        untilRemoved: static fn(): Code => Code::of('until removed'),
    )->shown();
}

it('keeps readings for thirty days as standard', function (): void {
    expect(howLongThatIs(HowLongReadingsAreKept::standard()))->toBe('30')
        ->and(HowLongReadingsAreKept::standard()->is(KeptFor::ThirtyDays))->toBeTrue();
});

it('holds a count past either end at that end', function (): void {
    expect(howLongThatIs(HowLongReadingsAreKept::days(0)))->toBe('1')
        ->and(howLongThatIs(HowLongReadingsAreKept::days(400)))->toBe('365')
        ->and(howLongThatIs(HowLongReadingsAreKept::days(45)))->toBe('45');
});

it('is the length offered by name when the count is the same', function (): void {
    expect(HowLongReadingsAreKept::days(365)->is(KeptFor::OneYear))->toBeTrue()
        ->and(HowLongReadingsAreKept::days(364)->is(KeptFor::OneYear))->toBeFalse()
        ->and(HowLongReadingsAreKept::untilRemoved()->is(KeptFor::OneYear))->toBeFalse();
});

it('keeps readings until removed apart from every count of days', function (): void {
    expect(howLongThatIs(HowLongReadingsAreKept::untilRemoved()))->toBe('until removed')
        ->and(HowLongReadingsAreKept::untilRemoved()->isUntilRemoved())->toBeTrue()
        ->and(HowLongReadingsAreKept::days(1)->isUntilRemoved())->toBeFalse();
});
