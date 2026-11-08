<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhatThisPhoneWakesThrough;
use Modules\Kernel\Api\WhoWakesThisPhone;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function ntfy(): ADistributor
{
    return ADistributor::of('io.heckel.ntfy', 'ntfy');
}

function nextPush(): ADistributor
{
    return ADistributor::of('org.unifiedpush.distributor.nextpush', 'NextPush');
}

/** Who wakes the phone, as one word. */
function whoWakes(WhoWakesThisPhone $who): string
{
    return $who->either(
        through: static fn(ADistributor $through, TheDistributors $installed): TheWordCarriedOut => new TheWordCarriedOut(sprintf('through %s of %d', $through->called(), count($installed))),
        toChoose: static fn(TheDistributors $installed): TheWordCarriedOut => new TheWordCarriedOut(sprintf('choose among %d', count($installed))),
        noneInstalled: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none installed'),
        notHere: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not here'),
    )->said;
}

it('is woken through the one distributor installed, whatever was chosen before', function (): void {
    expect(whoWakes(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::installed(TheDistributors::of(ntfy())), '')))->toBe('through ntfy of 1')
        ->and(whoWakes(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::installed(TheDistributors::of(ntfy())), nextPush()->package())))->toBe('through ntfy of 1');
});

it('asks which where several are installed and none of them was chosen, and is woken through the one chosen', function (): void {
    $both = WhatThisPhoneWakesThrough::installed(TheDistributors::of(ntfy(), nextPush()));

    expect(whoWakes(WhoWakesThisPhone::among($both, '')))->toBe('choose among 2')
        ->and(whoWakes(WhoWakesThisPhone::among($both, 'uninstalled.package')))->toBe('choose among 2')
        ->and(whoWakes(WhoWakesThisPhone::among($both, nextPush()->package())))->toBe('through NextPush of 2');
});

it('says none is installed apart from a phone that cannot be woken this way', function (): void {
    expect(whoWakes(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::installed(TheDistributors::of()), '')))->toBe('none installed')
        ->and(whoWakes(WhoWakesThisPhone::among(WhatThisPhoneWakesThrough::notHere(), 'io.heckel.ntfy')))->toBe('not here');
});

it('knows a distributor by its package, and calls one with no name by it', function (): void {
    $installed = TheDistributors::of(ntfy(), nextPush());

    expect(count($installed->onlyThe(ADistributor::of('io.heckel.ntfy', ''))))->toBe(1)
        ->and(count($installed->onlyThe(ADistributor::of('elsewhere', 'ntfy'))))->toBe(0)
        ->and(ntfy()->is(ADistributor::of('io.heckel.ntfy', 'another name')))->toBeTrue()
        ->and(ADistributor::of('io.heckel.ntfy', ' ')->called())->toBe('io.heckel.ntfy')
        ->and(ntfy()->package())->toBe('io.heckel.ntfy');
});

it('refuses a distributor with no package', function (): void {
    expect(static fn(): ADistributor => ADistributor::of(' ', 'ntfy'))->toThrow(AWakeIsUnfit::class);
});
