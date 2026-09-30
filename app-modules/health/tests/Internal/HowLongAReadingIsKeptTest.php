<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Internal;

use function expect;
use function it;

use Modules\Health\Internal\HowLongAReadingIsKept;
use Modules\Kernel\Api\Instant;

it('keeps a reading for thirty days as standard', function (): void {
    expect(HowLongAReadingIsKept::standard()->keepsWhatWasReadSince(Instant::atEpochSeconds(1_790_000_000))->epochSeconds())
        ->toBe(1_790_000_000 - 2_592_000);
});

it('keeps everything while the clock reads less than thirty days', function (): void {
    expect(HowLongAReadingIsKept::standard()->keepsWhatWasReadSince(Instant::atEpochSeconds(86_400))->epochSeconds())
        ->toBe(0);
});
