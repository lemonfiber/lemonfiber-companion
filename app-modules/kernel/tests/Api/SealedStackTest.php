<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\SealedStack;

it('hands a store exactly the hash it was built from', function (): void {
    expect(SealedStack::of('9f86d081884c7d65')->forTheStore())->toBe('9f86d081884c7d65');
});
