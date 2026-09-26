<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowTheStackIsRunning;

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

it('the order is worst first, which is the order a screen reads', function (): void {
    expect(array_map(static fn(HowTheStackIsRunning $r): string => $r->value, HowTheStackIsRunning::cases()))
        ->toBe(['inactive', 'degraded', 'partial', 'active']);
});
