<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\KeepingSaysNothing;

it('carries the name exactly as the stack listed it', function (): void {
    expect(ACopy::named(' lemonfiber-20260924-0300-full ')->name())->toBe(' lemonfiber-20260924-0300-full ');
});

it('refuses a copy nobody could name', function (): void {
    expect(fn(): ACopy => ACopy::named(" \t"))->toThrow(KeepingSaysNothing::class, '`archive`');
});
