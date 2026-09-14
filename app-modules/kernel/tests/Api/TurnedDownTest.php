<?php

declare(strict_types=1);

use Modules\Kernel\Api\RequestWasRefusedForNothing;
use Modules\Kernel\Api\TurnedDown;

/** One word carried out of `when()`, since it must hand back an object. */
final readonly class WhatTheRefusalSaid
{
    public function __construct(public string $said) {}
}

/** A refusal's moment, or the word for not having one. */
function whenItWasRefused(TurnedDown $why): string
{
    return $why->when(
        then: static fn(string $when): WhatTheRefusalSaid => new WhatTheRefusalSaid($when),
        unstated: static fn(): WhatTheRefusalSaid => new WhatTheRefusalSaid('unstated'),
    )->said;
}

it('N3-R7 — carries the reason that was given', function (): void {
    expect(TurnedDown::because('The disk is nearly full')->reason())
        ->toBe('The disk is nearly full');
});

it('D7-R7 — a refusal with no reason cannot be built', function (): void {
    // The reason is part of declining rather than an extra beside it, so a
    // decline without one is a stack that broke the rule — not a row to render
    // short, because *declined* with nothing after it is exactly the screen
    // that sends somebody to ask their operator in person.
    expect(fn(): TurnedDown => TurnedDown::because('   '))
        ->toThrow(RequestWasRefusedForNothing::class, 'waiting to be told why');

    expect(fn(): TurnedDown => TurnedDown::at('2026-09-14T04:00:00Z', ''))
        ->toThrow(RequestWasRefusedForNothing::class, 'waiting to be told why');
});

it('keeps the reason, less the whitespace around it', function (): void {
    expect(TurnedDown::because("  Not this week\n")->reason())->toBe('Not this week');
});

it('carries the moment in the words the stack used', function (): void {
    // Not re-rendered in the phone's timezone: two people in one house must not
    // disagree about when something happened on their machine.
    expect(whenItWasRefused(TurnedDown::at('2026-09-14T04:00:00Z', 'No room')))
        ->toBe('2026-09-14T04:00:00Z');
});

it('a refusal nobody timed is ordinary, not a fault', function (): void {
    expect(whenItWasRefused(TurnedDown::because('No room')))->toBe('unstated');
});
