<?php

declare(strict_types=1);

use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\RequestIsUnnumbered;
use Modules\Kernel\Api\RequestWasRefusedForNothing;
use Modules\Kernel\Api\WhatWasDecided;

/** One sentence carried out of a fold, so a test can read it. */
final readonly class WhatItCarried
{
    public function __construct(public string $said) {}
}

it('N2-R11 — an approval names the request and owes nothing else', function (): void {
    $decided = Decided::toApprove(RequestId::numbered(41));

    $why = $decided->why(
        was: static fn(string $because): object => new WhatItCarried($because),
        wasNot: static fn(): object => new WhatItCarried(''),
    );

    expect($decided->asked())->toBe('household-approve')
        ->and($decided->about()->number())->toBe(41)
        ->and($why->said)->toBe('');
});

it('D7-R7 — a refusal cannot be built without the sentence it owes', function (): void {
    // The rule as a type rather than as a habit: there is no road from here to
    // *declined* with nothing beside it, which is the screen that sends
    // somebody to ask their operator in person.
    expect(fn(): Decided => Decided::toDecline(RequestId::numbered(41), '   '))
        ->toThrow(RequestWasRefusedForNothing::class);
});

it('D7-R7 — and carries it, trimmed, where there is one', function (): void {
    $decided = Decided::toDecline(RequestId::numbered(41), '  No room this month  ');

    $why = $decided->why(
        was: static fn(string $because): object => new WhatItCarried($because),
        wasNot: static fn(): object => new WhatItCarried(''),
    );

    expect($decided->asked())->toBe('household-decline')
        ->and($why->said)->toBe('No room this month');
});

it('N2-R11 — a request is numbered from one, and nothing below it', function (): void {
    expect(RequestId::numbered(1)->number())->toBe(1)
        ->and(fn(): RequestId => RequestId::numbered(0))->toThrow(RequestIsUnnumbered::class)
        ->and(fn(): RequestId => RequestId::numbered(-1))->toThrow(RequestIsUnnumbered::class);
});

it('N2-R11 — one request is told from another by its number', function (): void {
    expect(RequestId::numbered(41)->is(RequestId::numbered(41)))->toBeTrue()
        ->and(RequestId::numbered(41)->is(RequestId::numbered(42)))->toBeFalse();
});

it('N2-R11 — the two decisions are asked for by the stack\'s own words', function (): void {
    // Written out rather than taken from the case's value, so the two
    // vocabularies may differ and a screen is not what notices when they do.
    $asked = array_map(
        static fn(WhatWasDecided $decided): string => $decided->asked(),
        WhatWasDecided::cases(),
    );

    expect($asked)->toBe(['household-approve', 'household-decline']);
});
