<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\Instant;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheReadingTook
{
    public function __construct(public string $said) {}
}

/** Which arm a reading takes, and what it carried there. */
function howFreshItIs(HowFreshAReadingIs $reading): string
{
    return $reading->either(
        live: static fn(): WhichArmTheReadingTook => new WhichArmTheReadingTook('live'),
        asOf: static fn(Instant $taken): WhichArmTheReadingTook => new WhichArmTheReadingTook(sprintf('as of %d', $taken->epochSeconds())),
    )->said;
}

it('keeps a live reading apart from one dated by when it was taken', function (): void {
    expect(howFreshItIs(HowFreshAReadingIs::live()))->toBe('live')
        ->and(howFreshItIs(HowFreshAReadingIs::asOf(Instant::atEpochSeconds(1_790_100_000))))->toBe('as of 1790100000');
});
