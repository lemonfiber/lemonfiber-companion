<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ItselfSaysNothing;
use Modules\Kernel\Api\WhatIsReleased;

it('keeps the version and what it changed', function (): void {
    $offer = WhatIsReleased::said('0.16.0', '- Plugins');

    expect([$offer->version(), $offer->changed()])->toBe(['0.16.0', '- Plugins']);
});

it('says nothing where nothing is on offer', function (): void {
    expect([WhatIsReleased::nothing()->version(), WhatIsReleased::nothing()->changed()])->toBe(['', ''])
        ->and([WhatIsReleased::said('', '')->version(), WhatIsReleased::said('', '')->changed()])->toBe(['', '']);
});

it('refuses a sentence that is blank rather than empty', function (): void {
    expect(fn(): WhatIsReleased => WhatIsReleased::said(' ', ''))->toThrow(ItselfSaysNothing::class, '`offered`')
        ->and(fn(): WhatIsReleased => WhatIsReleased::said('0.16.0', "\n"))->toThrow(ItselfSaysNothing::class, '`changed`');
});
