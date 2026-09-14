<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\WindowHoldsNoLines;

it('N2-R10 — carries the bound a read was given', function (): void {
    expect(HowManyLines::of(50)->figure())->toBe(50);
});

it('refuses a window with no lines in it', function (): void {
    // A read that asked for nothing has nothing to show for it.
    expect(fn(): HowManyLines => HowManyLines::of(0))
        ->toThrow(WindowHoldsNoLines::class, 'has nothing to show');

    expect(fn(): HowManyLines => HowManyLines::of(-4))
        ->toThrow(WindowHoldsNoLines::class, '-4');
});

it('one line is a window, which is where the refusal stops', function (): void {
    expect(HowManyLines::of(HowManyLines::AT_LEAST)->figure())->toBe(1);
});

it('what a phone asks for is decided once, here', function (): void {
    // Two screens showing different amounts of the same scrollback would have
    // an operator believe one of them was hiding something.
    expect(HowManyLines::asMuchAsAPhoneShows()->figure())->toBe(HowManyLines::ON_A_PHONE);
});
