<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\LongAfterAnyRun;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Instant;

it('spells one moment both ways', function (): void {
    $read = AMomentAsWritten::of(LongAfterAnyRun::WRITTEN)->read(
        static fn(Instant $moment): Instant => $moment,
        static fn(): Instant => Instant::atEpochSeconds(0),
    );

    expect($read->epochSeconds())->toBe(LongAfterAnyRun::IN_SECONDS);
});
