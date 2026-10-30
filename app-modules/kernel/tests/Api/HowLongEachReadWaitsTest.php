<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLongEachReadWaits;

it('holds the wait it was given, in milliseconds', function (): void {
    expect(HowLongEachReadWaits::ofMilliseconds(250)->milliseconds())->toBe(250);
});
