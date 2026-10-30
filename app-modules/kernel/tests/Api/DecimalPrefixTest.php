<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\RateUnit;
use Modules\Kernel\Api\SizeUnit;

it('bands sizes and rates by the decimal prefixes, each a thousand times the one before', function (): void {
    expect([RateUnit::Kilobits->bits(), RateUnit::Megabits->bits(), RateUnit::Gigabits->bits()])->toBe([1_000, 1_000_000, 1_000_000_000])
        ->and([SizeUnit::Megabytes->bytes(), SizeUnit::Gigabytes->bytes(), SizeUnit::Terabytes->bytes()])->toBe([1_000_000, 1_000_000_000, 1_000_000_000_000]);
});
