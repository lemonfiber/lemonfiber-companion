<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;

it('says a span in the coarsest unit it fills, with the bands an age uses', function (): void {
    expect(HowLongAgo::over(0))->toBe(HowLongAgo::Minutes)
        ->and(HowLongAgo::over(59))->toBe(HowLongAgo::Minutes)
        ->and(HowLongAgo::over(60))->toBe(HowLongAgo::Minutes)
        ->and(HowLongAgo::over(3_599))->toBe(HowLongAgo::Minutes)
        ->and(HowLongAgo::over(3_600))->toBe(HowLongAgo::Hours)
        ->and(HowLongAgo::over(86_399))->toBe(HowLongAgo::Hours)
        ->and(HowLongAgo::over(86_400))->toBe(HowLongAgo::Days);
});

it('measures an age and a span the stack counted the same way', function (): void {
    $then = Instant::atEpochSeconds(1_000);
    $now = Instant::atEpochSeconds(1_000 + 7_300);
    $span = HowLong::ofSeconds(7_300);

    expect(HowLongAgo::since($then, $now))->toBe($span->unit())
        ->and(HowLongAgo::Hours->howManySince($then, $now))->toBe($span->howMany());
});

it('names a span for the screen apart from an age', function (): void {
    expect(HowLongAgo::Hours->heldOnTheScreen())->toBe('health.held_for.hours')
        ->and(HowLongAgo::Hours->saidOnTheScreen())->toBe('health.ago.hours');
});
