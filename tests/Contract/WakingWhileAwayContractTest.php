<?php

declare(strict_types=1);

use Lemonfiber\Native\Wakes;
use Modules\Device\Api\PlatformWakes;
use Modules\Kernel\Api\WakingWhileAway;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\WakesInMemory;

// The WakingWhileAway contract, run against the adapter and against the fake.
//
// Every test of turning notifications on hands its subject a platform it named,
// so this file is the one place that says the platform's answer comes to the
// same thing: a platform that takes the request says so, and one with
// background refresh turned off says it did not.

/** @return array<string, array{Closure(): WakingWhileAway, Closure(): WakingWhileAway}> */
function everyPlatformThatWakesAndOneThatDoesNot(): array
{
    return [
        'the fake' => [
            static fn(): WakingWhileAway => WakesInMemory::thatWake(),
            static fn(): WakingWhileAway => WakesInMemory::thatNeverWake(),
        ],
        'the adapter' => [
            static function (): WakingWhileAway {
                FakeBridge::disable();
                FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Ask', ['outcome' => 'will_wake'])->respondTo('Lemonfiber.Wakes.Stop', ['outcome' => 'will_not_wake']);

                return new PlatformWakes(new Wakes());
            },
            static function (): WakingWhileAway {
                FakeBridge::disable();
                FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Ask', ['outcome' => 'refused', 'because' => 'unavailable']);

                return new PlatformWakes(new Wakes());
            },
        ],
    ];
}

afterEach(function (): void {
    FakeBridge::disable();
});

foreach (everyPlatformThatWakesAndOneThatDoesNot() as $name => [$wakes, $never]) {
    it(sprintf('%s answers that a platform that wakes took the request, every time it is asked', $name), function () use ($wakes): void {
        $platform = $wakes();

        expect($platform->ask())->toBeTrue()
            ->and($platform->ask())->toBeTrue();
    });

    it(sprintf('%s answers that a platform with background refresh off took nothing', $name), function () use ($never): void {
        expect($never()->ask())->toBeFalse();
    });

    it(sprintf('%s lets go when told to stop, and may be asked again after', $name), function () use ($wakes): void {
        $platform = $wakes();

        expect($platform->stop())->toBeTrue()
            ->and($platform->ask())->toBeTrue();
    });
}

// Beyond the contract: what each says about what it was told.

it('asks the bridge for a wake', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Ask', ['outcome' => 'will_wake']);

    expect(new PlatformWakes(new Wakes())->ask())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Wakes.Ask');
});

it('tells the bridge to stop', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Wakes.Stop', ['outcome' => 'will_not_wake']);

    expect(new PlatformWakes(new Wakes())->stop())->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Wakes.Stop');
});

it('remembers in the fake whether a wake is asked for', function (): void {
    $platform = WakesInMemory::thatWake();

    expect($platform->isAsked())->toBeFalse();

    $platform->ask();

    expect($platform->isAsked())->toBeTrue();

    $platform->stop();

    expect($platform->isAsked())->toBeFalse()
        ->and(WakesInMemory::thatNeverWake()->isAsked())->toBeFalse();
});
