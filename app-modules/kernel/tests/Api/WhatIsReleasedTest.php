<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ItselfSaysNothing;
use Modules\Kernel\Api\WhatIsReleased;

it('keeps the version', function (): void {
    expect(WhatIsReleased::said('0.16.0')->version())->toBe('0.16.0');
});

it('says nothing where nothing is on offer', function (): void {
    expect(WhatIsReleased::nothing()->version())->toBe('')
        ->and(WhatIsReleased::said('')->version())->toBe('');
});

it('refuses a version that is blank rather than empty', function (): void {
    expect(fn(): WhatIsReleased => WhatIsReleased::said(' '))->toThrow(ItselfSaysNothing::class, '`offered`');
});
