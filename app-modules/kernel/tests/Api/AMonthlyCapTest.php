<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhereTheMonthStands;

/** One line carried out of an arm. */
final readonly class WhereTheCapWasSaidToStand
{
    public function __construct(public string $said) {}
}

/** Where a cap's month stands, or the word for uncounted. */
function whereTheMonthStandsOn(AMonthlyCap $cap): string
{
    return $cap->stands(
        stands: static fn(WhereTheMonthStands $month): WhereTheCapWasSaidToStand => new WhereTheCapWasSaidToStand($month->value),
        uncounted: static fn(): WhereTheCapWasSaidToStand => new WhereTheCapWasSaidToStand('uncounted'),
    )->said;
}

it('N10-R6 — carries its allowance and which of pause, throttle or continue it does', function (): void {
    $cap = AMonthlyCap::of(1_000_000_000_000, WhatACapDoes::Throttle);

    expect($cap->monthly())->toBe(1_000_000_000_000)->and($cap->does())->toBe(WhatACapDoes::Throttle);
});

it('N10-R7 — a cap of zero is a cap', function (): void {
    expect(AMonthlyCap::of(0, WhatACapDoes::Pause)->monthly())->toBe(0);
});

it('says where the month stands only where something counted it', function (): void {
    $cap = AMonthlyCap::of(500, WhatACapDoes::Continue);

    expect(whereTheMonthStandsOn($cap))->toBe('uncounted')
        ->and(whereTheMonthStandsOn($cap->standing(WhereTheMonthStands::Warning)))->toBe('warning')
        ->and($cap->standing(WhereTheMonthStands::Warning)->does())->toBe(WhatACapDoes::Continue)
        ->and($cap->standing(WhereTheMonthStands::Warning)->monthly())->toBe(500);
});

it('refuses an allowance below zero', function (): void {
    expect(fn(): AMonthlyCap => AMonthlyCap::of(-1, WhatACapDoes::Pause))->toThrow(LineSaysNothing::class, '`monthly` is -1');
});
