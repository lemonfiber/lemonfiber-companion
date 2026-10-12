<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatTheLineRunDid;

it('reads a rehearsal as one whatever it claims to have written, then a write, then a reading', function (): void {
    expect(WhatTheLineRunDid::said(rehearsed: true, applied: true))->toBe(WhatTheLineRunDid::Rehearsed)
        ->and(WhatTheLineRunDid::said(rehearsed: true, applied: false))->toBe(WhatTheLineRunDid::Rehearsed)
        ->and(WhatTheLineRunDid::said(rehearsed: false, applied: true))->toBe(WhatTheLineRunDid::Applied)
        ->and(WhatTheLineRunDid::said(rehearsed: false, applied: false))->toBe(WhatTheLineRunDid::OnlyRead);
});

it('names a catalogue line for each', function (): void {
    expect(WhatTheLineRunDid::Applied->saidOnTheScreen())->toBe('stacks.line_change.did.applied');
});
