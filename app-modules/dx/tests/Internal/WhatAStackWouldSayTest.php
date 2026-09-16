<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\WhatAStackWouldSay;

// What the synthesiser builds from notation the installed contract never uses.
//
// The sweep beside this one asks the question that matters — that every one of
// the contract's envelopes comes out as a payload the contract accepts — and it
// can only exercise the notation the SDK actually writes. These cover the rest
// of what the reader understands, because an arm nobody reaches is
// indistinguishable from an arm that is wrong.

it('builds nothing for an envelope it cannot read', function (): void {
    // A stand-in answering confidently about an envelope nobody has is worse
    // than one answering nothing: the payload would be invented rather than
    // derived, which is the one thing `N1-R59` exists to prevent.
    expect(WhatAStackWouldSay::inside('NoSuchEnvelope'))->toBeNull();
});

it('builds nothing for a type that is nothing but absence', function (): void {
    expect(WhatAStackWouldSay::forThe('null', 'a_field'))->toBeNull()
        ->and(WhatAStackWouldSay::forThe('null|null', 'a_field'))->toBeNull()
        ->and(WhatAStackWouldSay::forThe('', 'a_field'))->toBeNull();
});

it('hands back a number the contract wrote as one', function (): void {
    // A reader checking `is_int` on a field declared `0` would refuse the
    // string `'0'`, and JSON carries the difference — so a literal number in
    // the notation has to come back as a number of the same kind.
    expect(WhatAStackWouldSay::forThe('3', 'a_field'))->toBe(3)
        ->and(WhatAStackWouldSay::forThe('1.5', 'a_field'))->toBe(1.5);
});
