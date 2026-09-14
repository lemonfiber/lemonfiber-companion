<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowMuchIsShown;

it('N2-R9 — both cases have a line, so silence is never the answer', function (): void {
    // `AllOfIt` has a line of its own rather than rendering as nothing. A
    // screen that says nothing when a list is whole teaches an operator to read
    // silence, and silence is also what a screen that forgot the flag produces.
    expect(HowMuchIsShown::AllOfIt->saidOnTheScreen())->toBe('health.shown.all-of-it')
        ->and(HowMuchIsShown::SomeOfIt->saidOnTheScreen())->toBe('health.shown.some-of-it');
});

it('only one of the two is the whole of what the stack holds', function (): void {
    expect(HowMuchIsShown::AllOfIt->isTheWhole())->toBeTrue()
        ->and(HowMuchIsShown::SomeOfIt->isTheWhole())->toBeFalse();
});
