<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\WhatTheContractDeclares;

// What the reader answers about a declaration it does not have.
//
// Everything else that uses this class runs it over the installed contract,
// which is the right way round: what it says about `StatusEnvelope` is the
// thing that matters, and a synthetic type would prove nothing about it.
//
// These ask the opposite question, because the contract is not the only thing
// this will ever be handed — an envelope renamed upstream, a kind nothing
// carries — and each of those is a path the reader has and the installed SDK
// never takes. A path nothing exercises is a path that stops working silently,
// and the coverage floor is what makes that concrete rather than a worry.

it('answers nothing for an envelope the contract does not have', function (): void {
    // Nothing rather than a raise, because every caller has a sentence for an
    // empty reading and none has a sentence for an exception arriving out of
    // an expectation.
    expect(WhatTheContractDeclares::kindOf('NoSuchEnvelope'))->toBe('')
        ->and(WhatTheContractDeclares::shapeOf('NoSuchEnvelope'))->toBe('');
});

it('answers nothing for a kind no envelope carries', function (): void {
    expect(WhatTheContractDeclares::envelopeOfKind('nothing-reads-this'))->toBe('');
});
