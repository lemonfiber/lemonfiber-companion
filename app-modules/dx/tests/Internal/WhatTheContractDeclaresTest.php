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

it('reads only the fields of a shape, not the mark that leaves it open', function (): void {
    // An unsealed shape ends in `...`, which names no field; reading it as one
    // would report a field the wire never carries.
    expect(WhatTheContractDeclares::fieldsOf('array{name: string, ...}'))->toBe(['name' => [false, 'string']])
        ->and(WhatTheContractDeclares::fieldsOf('array{}'))->toBe([]);
});

it('spells out a shape the package names rather than writes', function (): void {
    // The installed contract writes every payload as a name, so a reading that
    // stopped at the name would hand every caller a type with no brackets.
    expect(WhatTheContractDeclares::shapeOf('ErrorEnvelope'))->toStartWith('array{')
        ->toContain('remedies: list<array{');
});

it('leaves a literal, a field and a name nothing declares as they are written', function (): void {
    expect(WhatTheContractDeclares::spelledOut("array{Code: 'Code', code: Code, other: Unknown}", ['Code' => 'string']))
        ->toBe("array{Code: 'Code', code: string, other: Unknown}");
});

it('stops spelling out a shape that holds itself', function (): void {
    expect(WhatTheContractDeclares::spelledOut('Problem', ['Problem' => 'array{cause?: Problem|null}']))
        ->toBe('array{cause?: mixed|null}');
});
