<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\Remark;

it('carries the stack\'s sentence as written', function (): void {
    expect(Remark::said('Fetching has stopped until the 1st', 'acting')->words())->toBe('Fetching has stopped until the 1st');
});

it('refuses a blank, naming the field it was to fill', function (): void {
    expect(fn(): Remark => Remark::said(' ', 'ratio'))->toThrow(LineSaysNothing::class, '`ratio`');
});
