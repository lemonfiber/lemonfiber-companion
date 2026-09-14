<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Overall;

it('N2-R7 — says what the whole stack amounts to, as a key', function (): void {
    foreach (HowTheStackIsRunning::cases() as $running) {
        expect($running->saidOnTheScreen())->toBe(sprintf('health.running.%s', $running->value));
    }
});

it('only one of the four is everything running', function (): void {
    expect(HowTheStackIsRunning::Active->isAllOfIt())->toBeTrue()
        ->and(HowTheStackIsRunning::Partial->isAllOfIt())->toBeFalse()
        ->and(HowTheStackIsRunning::Degraded->isAllOfIt())->toBeFalse()
        ->and(HowTheStackIsRunning::Inactive->isAllOfIt())->toBeFalse();
});

it('what is running is not what the checks concluded', function (): void {
    // A stack can pass every check while half its services are stopped —
    // somebody turned them off — and it can be `active` with a finding against
    // it. Folding the two would make one of those states unsayable, so they are
    // separate enums with separate catalogue groups.
    $running = array_map(static fn(HowTheStackIsRunning $r): string => $r->saidOnTheScreen(), HowTheStackIsRunning::cases());
    $concluded = array_map(static fn(Overall $o): string => $o->saidOnTheScreen(), Overall::cases());

    expect(array_intersect($running, $concluded))->toBe([]);
});

it('the order is worst first, which is the order a screen reads', function (): void {
    expect(array_map(static fn(HowTheStackIsRunning $r): string => $r->value, HowTheStackIsRunning::cases()))
        ->toBe(['inactive', 'degraded', 'partial', 'active']);
});
