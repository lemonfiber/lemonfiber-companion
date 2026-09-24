<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ItselfSaysNothing;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;

it('keeps what an update carries and what it leaves behind', function (): void {
    $brings = WhatAnUpdateWouldBring::said('The program', 'Settings are kept');

    expect([$brings->carries(), $brings->afterwards()])->toBe(['The program', 'Settings are kept']);
});

it('refuses either sentence blank, naming which', function (): void {
    expect(fn(): WhatAnUpdateWouldBring => WhatAnUpdateWouldBring::said(' ', 'Settings are kept'))->toThrow(ItselfSaysNothing::class, '`carries`')
        ->and(fn(): WhatAnUpdateWouldBring => WhatAnUpdateWouldBring::said('The program', ''))->toThrow(ItselfSaysNothing::class, '`afterwards`');
});
