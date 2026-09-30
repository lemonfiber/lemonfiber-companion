<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\HowManyLines;
use Modules\Operator\Internal\ALogWindow;

it('offers fifty lines, as many as a phone shows, and a thousand, fewest first', function (): void {
    expect(ALogWindow::Fewer->lines()->figure())->toBe(50)
        ->and(ALogWindow::AsOnAPhone->lines()->figure())->toBe(HowManyLines::ON_A_PHONE)
        ->and(ALogWindow::More->lines()->figure())->toBe(1_000);
});
