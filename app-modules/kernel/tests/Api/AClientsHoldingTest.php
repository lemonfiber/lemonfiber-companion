<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatALimitCameTo;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a client's answer took, and what it carried there. */
function whichArmTheClientTook(AClientsHolding $client): string
{
    return $client->either(
        answered: static fn(ADirectionHeld $down, ADirectionHeld $up): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered:%s/%s', $down->verdict()->value, $up->verdict()->value)),
        silent: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut(sprintf('silent:%s', $said)),
    )->said;
}

it('carries a client that answered for both directions, by name, with whether it is fetching', function (): void {
    $client = AClientsHolding::answered(' qbittorrent ', WhatADownloadClientSaid::Fetching, ADirectionHeld::said(WhatALimitCameTo::Holding), ADirectionHeld::said(WhatALimitCameTo::Unasked));

    expect([$client->client(), $client->pulling(), whichArmTheClientTook($client)])
        ->toBe(['qbittorrent', WhatADownloadClientSaid::Fetching, 'answered:holding/unasked']);
});

it('carries a client that did not answer with what it said instead', function (): void {
    $client = AClientsHolding::silent('sabnzbd', WhatADownloadClientSaid::Unsaid, ' connection refused ');

    expect([$client->client(), whichArmTheClientTook($client)])->toBe(['sabnzbd', 'silent:connection refused']);
});

it('refuses a client named nothing', function (): void {
    expect(static fn(): AClientsHolding => AClientsHolding::silent(' ', WhatADownloadClientSaid::Unsaid, 'no'))
        ->toThrow(LineSaysNothing::class, '`client`');
});
