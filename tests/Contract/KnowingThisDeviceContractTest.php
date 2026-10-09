<?php

declare(strict_types=1);

use Modules\Connection\Api\ThisDeviceKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KnowingThisDevice;
use Tests\Support\Fakes\ADeviceWithAnId;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;

// The KnowingThisDevice contract, run against the adapter and against the fake.
//
// What both must agree on: an id the core accepts, and the same one every
// time it is asked for.

/** @return array<string, array{Closure(): KnowingThisDevice}> */
dataset('every way of knowing this device', [
    'kept with the settings' => [fn(): KnowingThisDevice => new ThisDeviceKept(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), SequencedEntropy::counting(), FrozenClock::at(Instant::atEpochSeconds(0)))],
    'the fake' => [fn(): KnowingThisDevice => ADeviceWithAnId::named('a-device-id')],
]);

it('answers an id the core accepts, the same one every time', function (KnowingThisDevice $knowing): void {
    $first = $knowing->thisDevice()->shown();

    expect($first)->toMatch('/\A[A-Za-z0-9-]{8,64}\z/')
        ->and($knowing->thisDevice()->shown())->toBe($first);
})->with('every way of knowing this device');
