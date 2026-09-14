<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowMuchItMatters;

it('N2-R7 — says how much a service matters, as a key', function (): void {
    foreach (HowMuchItMatters::cases() as $matters) {
        expect($matters->saidOnTheScreen())->toBe(sprintf('health.matters.%s', $matters->value));
    }
});

it('N2-R8 — stopping the stack\'s own machinery disturbs the house', function (): void {
    // *Disruptive* is not a property of the verb. Stopping an optional service
    // disturbs nobody; stopping a critical one takes the evening with it.
    expect(HowMuchItMatters::Critical->stoppingItDisturbsTheHouse())->toBeTrue()
        ->and(HowMuchItMatters::Core->stoppingItDisturbsTheHouse())->toBeTrue();
});

it('N2-R8 — and stopping the rest does not', function (): void {
    expect(HowMuchItMatters::Important->stoppingItDisturbsTheHouse())->toBeFalse()
        ->and(HowMuchItMatters::Enhancing->stoppingItDisturbsTheHouse())->toBeFalse()
        ->and(HowMuchItMatters::Optional->stoppingItDisturbsTheHouse())->toBeFalse();
});

it('the order is worst first, which is the order a screen reads', function (): void {
    expect(array_map(static fn(HowMuchItMatters $m): string => $m->value, HowMuchItMatters::cases()))
        ->toBe(['critical', 'core', 'important', 'enhancing', 'optional']);
});
