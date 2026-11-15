<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhoWakesThisPhone;

use function sprintf;

use Tests\Support\AWakingPhone;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\TheWordCarriedOut;

/** Who wakes the phone, as one word. */
function whoWakesThePhone(WhoWakesThisPhone $who): string
{
    return $who->either(
        through: static fn(ADistributor $through): TheWordCarriedOut => new TheWordCarriedOut(sprintf('through %s', $through->called())),
        toChoose: static fn(TheDistributors $installed): TheWordCarriedOut => new TheWordCarriedOut(sprintf('choose among %d', count($installed))),
        noneInstalled: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none installed'),
        notHere: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not here'),
    )->said;
}

it('asks which distributor among several until one is chosen, and keeps the choice with the phone\'s settings', function (): void {
    $settings = ConnectionSettingsInMemory::empty();
    $phone = DistributorsOnAPhone::with(DistributorsOnAPhone::ntfy(), DistributorsOnAPhone::nextPush());
    $choosing = AWakingPhone::choosing($phone, $settings);

    expect(whoWakesThePhone($choosing->who()))->toBe('choose among 2')
        ->and(whoWakesThePhone($choosing->choose(DistributorsOnAPhone::nextPush())))->toBe('through NextPush')
        ->and($choosing->wakesThrough(DistributorsOnAPhone::nextPush()))->toBeTrue()
        ->and($choosing->wakesThrough(DistributorsOnAPhone::ntfy()))->toBeFalse();
});

it('finds a distributor by its package among those installed, and none on a phone that cannot be woken this way', function (): void {
    $choosing = AWakingPhone::choosing(DistributorsOnAPhone::with());

    expect(count($choosing->installedAs(ADistributor::of('io.heckel.ntfy', ''))))->toBe(1)
        ->and(count($choosing->installedAs(DistributorsOnAPhone::nextPush())))->toBe(0)
        ->and(count(AWakingPhone::choosing()->installedAs(DistributorsOnAPhone::ntfy())))->toBe(0)
        ->and(AWakingPhone::choosing()->wakesThrough(DistributorsOnAPhone::ntfy()))->toBeFalse()
        ->and(AWakingPhone::choosing(DistributorsOnAPhone::withNone())->wakesThrough(DistributorsOnAPhone::ntfy()))->toBeFalse()
        ->and(whoWakesThePhone(AWakingPhone::choosing(DistributorsOnAPhone::withNone())->who()))->toBe('none installed');
});
