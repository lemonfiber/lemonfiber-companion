<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\Instant;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a reading takes, and what it carried there. */
function howFreshItIs(HowFreshAReadingIs $reading): string
{
    return $reading->either(
        live: static fn(): TheWordCarriedOut => new TheWordCarriedOut('live'),
        asOf: static fn(Instant $taken): TheWordCarriedOut => new TheWordCarriedOut(sprintf('as of %d', $taken->epochSeconds())),
    )->said;
}

it('keeps a live reading apart from one dated by when it was taken', function (): void {
    expect(howFreshItIs(HowFreshAReadingIs::live()))->toBe('live')
        ->and(howFreshItIs(HowFreshAReadingIs::asOf(Instant::atEpochSeconds(1_790_100_000))))->toBe('as of 1790100000');
});
