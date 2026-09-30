<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\AnAddressThatResolvesNowhere;

it('puts the machine under the reserved name, on the port a stack serves', function (): void {
    expect(AnAddressThatResolvesNowhere::of('answering'))->toBe('https://answering.invalid:8443');
});
