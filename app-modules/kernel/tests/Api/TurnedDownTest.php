<?php

declare(strict_types=1);

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\RequestWasRefusedForNothing;
use Modules\Kernel\Api\TurnedDown;
use Tests\Support\TheWordCarriedOut;

/** A refusal's moment, or the word for not having one. */
function whenItWasRefused(TurnedDown $why): string
{
    return $why->when()->read(
        read: static fn(Instant $when): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d', $when->epochSeconds())),
        unreadable: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unstated'),
    )->said;
}

it('carries the reason that was given', function (): void {
    expect(TurnedDown::because('The disk is nearly full')->reason())
        ->toBe('The disk is nearly full');
});

it('a refusal with no reason cannot be built', function (): void {
    // The reason is part of declining rather than an extra beside it, so a
    // decline without one is a stack that broke the rule — not a row to render
    // short, because *declined* with nothing after it is exactly the screen
    // that sends somebody to ask their operator in person.
    expect(fn(): TurnedDown => TurnedDown::because('   '))
        ->toThrow(RequestWasRefusedForNothing::class, 'waiting to be told why');

    expect(fn(): TurnedDown => TurnedDown::at(AMomentAsWritten::byTheStack(1_789_358_400), ''))
        ->toThrow(RequestWasRefusedForNothing::class, 'waiting to be told why');
});

it('keeps the reason, less the whitespace around it', function (): void {
    expect(TurnedDown::because("  Not this week\n")->reason())->toBe('Not this week');
});

it('carries the moment the stack counted', function (): void {
    expect(whenItWasRefused(TurnedDown::at(AMomentAsWritten::byTheStack(1_789_358_400), 'No room')))
        ->toBe('1789358400');
});

it('a refusal nobody timed is ordinary, not a fault', function (): void {
    expect(whenItWasRefused(TurnedDown::because('No room')))->toBe('unstated');
});
