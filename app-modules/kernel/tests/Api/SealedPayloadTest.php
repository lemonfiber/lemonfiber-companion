<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\SealedPayload;

it('hands a store exactly the payload it was built from', function (): void {
    expect(SealedPayload::of('eyJpdiI6IiJ9')->forTheStore())->toBe('eyJpdiI6IiJ9');
});

it('takes a string that is not a payload, for opening to refuse', function (): void {
    expect(SealedPayload::of('')->forTheStore())->toBe('');
});
