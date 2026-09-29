<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedReading;
use Modules\Kernel\Api\Shape;

it('hands back the payload, the shape and the moment it was built with', function (): void {
    $reading = SealedReading::of(SealedPayload::of('a-sealed-summary'), Shape::One, Instant::atEpochSeconds(1_790_000_000));

    expect($reading->payload()->forTheStore())->toBe('a-sealed-summary')
        ->and($reading->shape())->toBe(Shape::One)
        ->and($reading->readAt()->epochSeconds())->toBe(1_790_000_000);
});
