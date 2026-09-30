<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\TimeOfDay;

it('shows the time as a 24-hour clock does, to the second', function (int $seconds, string $shown): void {
    expect(TimeOfDay::secondsIntoTheDay($seconds)->shown())->toBe($shown);
})->with([
    'midnight' => [0, '00:00:00'],
    'a second before midnight' => [86_399, '23:59:59'],
    'the evening' => [78_541, '21:49:01'],
    'a single-figure minute and second' => [3_723, '01:02:03'],
]);

it('folds a count past midnight into the next day', function (): void {
    expect(TimeOfDay::secondsIntoTheDay(86_400 + 61)->shown())->toBe('00:01:01');
});

it('folds a count before midnight into the evening before', function (): void {
    // A moment just after midnight UTC, on a clock behind UTC, is the evening
    // before rather than a negative hour.
    expect(TimeOfDay::secondsIntoTheDay(-60)->shown())->toBe('23:59:00');
});
