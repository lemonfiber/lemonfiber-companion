<?php

declare(strict_types=1);

use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\RequestIsUnnumbered;
use Modules\Kernel\Api\RequestWasRefusedForNothing;
use Modules\Kernel\Api\WhatWasDecided;
use Tests\Support\TheWordCarriedOut;

it('an approval names the request and owes nothing else', function (): void {
    $decided = Decided::toApprove(RequestId::numbered(41));

    $why = $decided->why(
        was: static fn(string $because): object => new TheWordCarriedOut($because),
        // A word of its own rather than a blank, so that swapping the arms is
        // a failure rather than two ways of saying nothing.
        wasNot: static fn(): object => new TheWordCarriedOut('nothing was owed'),
    );

    expect($decided->asked())->toBe('household-approve')
        ->and($decided->about()->number())->toBe(41)
        ->and($why->said)->toBe('nothing was owed');
});

it('a refusal cannot be built without the sentence it owes', function (): void {
    // The rule as a type rather than as a habit: there is no road from here to
    // *declined* with nothing beside it, which is the screen that sends
    // somebody to ask their operator in person.
    expect(fn(): Decided => Decided::toDecline(RequestId::numbered(41), '   '))
        ->toThrow(RequestWasRefusedForNothing::class);
});

it('carries the sentence a refusal owes, trimmed, where there is one', function (): void {
    $decided = Decided::toDecline(RequestId::numbered(41), '  No room this month  ');

    $why = $decided->why(
        was: static fn(string $because): object => new TheWordCarriedOut($because),
        wasNot: static fn(): object => new TheWordCarriedOut('nothing was owed'),
    );

    expect($decided->asked())->toBe('household-decline')
        ->and($why->said)->toBe('No room this month');
});

it('a request is numbered from one, and nothing below it', function (): void {
    expect(RequestId::numbered(1)->number())->toBe(1)
        ->and(fn(): RequestId => RequestId::numbered(0))->toThrow(RequestIsUnnumbered::class)
        ->and(fn(): RequestId => RequestId::numbered(-1))->toThrow(RequestIsUnnumbered::class);
});

it('one request is told from another by its number', function (): void {
    expect(RequestId::numbered(41)->is(RequestId::numbered(41)))->toBeTrue()
        ->and(RequestId::numbered(41)->is(RequestId::numbered(42)))->toBeFalse();
});

it('the two decisions are asked for by the stack\'s own words', function (): void {
    // Written out rather than taken from the case's value, so the two
    // vocabularies may differ and a screen is not what notices when they do.
    $asked = array_map(
        static fn(WhatWasDecided $decided): string => $decided->asked(),
        WhatWasDecided::cases(),
    );

    expect($asked)->toBe(['household-approve', 'household-decline']);
});
