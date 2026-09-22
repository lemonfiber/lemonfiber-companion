<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\Wiring;

it('carries the service doing the reaching as well as what it reaches', function (): void {
    // A wiring read without the first half is a statement that something
    // reaches a download client, which answers no question an operator asks.
    // They ask why *this* service is talking to *that* one, and both halves are
    // needed to answer. The reach is handed back as it was given rather than
    // unpacked here — what it means is `HowItReaches`' own test.
    $reaches = HowItReaches::asked(
        Capability::called('download-client'),
        Services::these(ServiceId::called('sabnzbd')),
        WhatSettledIt::outright(),
    );

    $wiring = Wiring::of(ServiceId::called('sonarr'), $reaches);

    expect($wiring->by()->named())->toBe('sonarr')
        ->and($wiring->reaches())->toBe($reaches);
});

it('carries a by-name reach as given too', function (): void {
    $reaches = HowItReaches::byName(ServiceId::called('qbittorrent'), 'the operator said so');

    expect(Wiring::of(ServiceId::called('sonarr'), $reaches)->reaches())->toBe($reaches);
});
