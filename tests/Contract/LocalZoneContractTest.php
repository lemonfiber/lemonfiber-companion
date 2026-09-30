<?php

declare(strict_types=1);

use Lemonfiber\Native\Clock;
use Modules\Device\Api\PlatformZone;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LocalZone;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\AZoneThatIsSet;

// The LocalZone contract, run against the adapter and against the fake.
//
// Every screen test hands its subject a phone set to a zone it named, so this
// file is the one place that says the platform's answer comes to the same
// thing: the zone the phone is set to, by its name, and the clock it reads.

/** Nine in the evening, UTC, on 29 September 2026: a moment in summer time in Amsterdam. */
const IN_THE_EVENING = 1_790_717_941;

/** @return array<string, Closure(): LocalZone> */
function everyPhoneSetToAmsterdam(): array
{
    return [
        'the fake' => static fn(): LocalZone => AZoneThatIsSet::to('Europe/Amsterdam'),
        'the adapter' => static function (): LocalZone {
            FakeBridge::disable();
            FakeBridge::enable()->respondTo('Lemonfiber.Clock.Zone', ['outcome' => 'known', 'zone' => 'Europe/Amsterdam']);

            return new PlatformZone(new Clock());
        },
    ];
}

afterEach(function (): void {
    FakeBridge::disable();
});

foreach (everyPhoneSetToAmsterdam() as $name => $build) {
    it(sprintf('%s answers the zone the phone is set to', $name), function () use ($build): void {
        expect($build()->zone()->name())->toBe('Europe/Amsterdam');
    });

    it(sprintf('%s reads the clock as it stood in that zone at the moment', $name), function () use ($build): void {
        expect($build()->zone()->timeOfDayAt(Instant::atEpochSeconds(IN_THE_EVENING))->shown())->toBe('23:39:01');
    });
}

// Beyond the contract: what the adapter does where the platform says nothing useful.

it('reads a phone that names no zone as set to UTC', function (): void {
    // Every machine that is not a handset.
    FakeBridge::disable();
    FakeBridge::enable();

    expect(new PlatformZone(new Clock())->zone()->name())->toBe('UTC');
});

it('reads a zone the runtime cannot place as UTC', function (): void {
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Clock.Zone', ['outcome' => 'known', 'zone' => 'Nowhere/Atall']);

    expect(new PlatformZone(new Clock())->zone()->name())->toBe('UTC');
});
