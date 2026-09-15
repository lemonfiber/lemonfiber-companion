<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\CodeIsBlank;
use Modules\Kernel\Api\WhatTheCoreDecided;

it('carries the identifier the core decided to say', function (): void {
    expect(WhatTheCoreDecided::toSay('backup.finished')->shown())->toBe('backup.finished');
});

it('is shown without the whitespace around it', function (): void {
    // It keys the words a banner draws and is what somebody searches for
    // afterwards. Padding is invisible in the payload and is a catalogue key
    // that resolves to nothing.
    expect(WhatTheCoreDecided::toSay("  backup.finished\n")->shown())->toBe('backup.finished');
});

it('N4-R11 — refuses a decision that names nothing', function (): void {
    // Whitespace as well as empty, which is the case a length check alone lets
    // through: a notification with no identifier has nothing to key its words
    // by, and renders as a banner with a blank line where the sentence goes.
    expect(fn(): WhatTheCoreDecided => WhatTheCoreDecided::toSay(''))
        ->toThrow(CodeIsBlank::class, 'A notification decision arrived with no code')
        ->and(fn(): WhatTheCoreDecided => WhatTheCoreDecided::toSay("  \t\n"))
        ->toThrow(CodeIsBlank::class);
});
