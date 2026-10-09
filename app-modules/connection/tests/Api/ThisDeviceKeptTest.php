<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\LockingAfter;
use Modules\Connection\Api\ThisDeviceKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LockAfter;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;

/** The id kept over these. Named for this file. */
function theDeviceKeptOver(ASealInMemory $seal, ConnectionSettingsInMemory $kept, SequencedEntropy $entropy): ThisDeviceKept
{
    return new ThisDeviceKept($seal, $kept, $entropy, FrozenClock::at(Instant::atEpochSeconds(0)));
}

it('draws the id once and keeps it across launches', function (): void {
    $seal = ASealInMemory::working();
    $kept = ConnectionSettingsInMemory::empty();
    $entropy = SequencedEntropy::counting();
    $first = theDeviceKeptOver($seal, $kept, $entropy)->thisDevice()->shown();

    expect(theDeviceKeptOver($seal, $kept, $entropy)->thisDevice()->shown())->toBe($first)
        ->and($entropy->answered())->toBe(1);
});

it('keeps the settings beside the id, and the id beside a setting chosen later', function (): void {
    $seal = ASealInMemory::working();
    $kept = ConnectionSettingsInMemory::empty();
    $locking = new LockingAfter($seal, $kept, ADeviceThatKnowsYou::unlocked(), FrozenClock::at(Instant::atEpochSeconds(0)));
    $locking->choose(LockAfter::OneHour);
    $entropy = SequencedEntropy::counting();
    $first = theDeviceKeptOver($seal, $kept, $entropy)->thisDevice()->shown();
    $locking->choose(LockAfter::FiveMinutes);

    expect($locking->current())->toBe(LockAfter::FiveMinutes)
        ->and(theDeviceKeptOver($seal, $kept, $entropy)->thisDevice()->shown())->toBe($first)
        ->and($entropy->answered())->toBe(1);
});

it('draws a new id each time where the phone can keep nothing', function (): void {
    $seal = ASealInMemory::withNoSecureStorage();
    $kept = ConnectionSettingsInMemory::empty();
    $entropy = SequencedEntropy::counting();
    $device = theDeviceKeptOver($seal, $kept, $entropy);

    expect($device->thisDevice()->shown())->not->toBe($device->thisDevice()->shown());
});
