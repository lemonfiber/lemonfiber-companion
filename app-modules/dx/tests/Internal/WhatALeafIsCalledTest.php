<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\WhatALeafIsCalled;

// The four words, and what each is worth.
//
// `false` is the one the installed contract does not use today. It stays in the
// vocabulary because the notation has it and a reader meeting it would have to
// answer something — and it is asserted here for that exact reason: a case
// nothing reaches is a case nothing would notice breaking.

it('knows what each word for a leaf is worth', function (): void {
    expect(WhatALeafIsCalled::Bool->carries())->toBeTrue()
        ->and(WhatALeafIsCalled::True->carries())->toBeTrue()
        ->and(WhatALeafIsCalled::False->carries())->toBeFalse()
        ->and(WhatALeafIsCalled::Null->carries())->toBeNull();
});
