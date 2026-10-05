<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;

it('keeps the links in the order the stack declares them, each with the service it runs from', function (): void {
    $links = TheLinks::of(
        WhatNothingFills::none(),
        ALink::from(ServiceId::called('jellyfin'), HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs elsewhere')),
        ALink::from(ServiceId::called('sonarr'), HowItReaches::byName(ServiceId::called('qbittorrent'), 'The operator said so')),
    );
    $from = [];

    foreach ($links as $link) {
        $from[] = $link->by()->named();
    }

    expect($from)->toBe(['jellyfin', 'sonarr'])
        ->and(array_keys(iterator_to_array($links, preserve_keys: true)))->toBe([0, 1]);
});

it('carries what nothing fills beside the links, and none as an answer', function (): void {
    $unfilled = WhatNothingFills::these(Unfilled::of(ServiceId::called('lidarr'), Capability::called('music-tagger')));

    expect(TheLinks::of($unfilled)->unfilled())->toBe($unfilled)
        ->and(iterator_to_array(TheLinks::of(WhatNothingFills::none()), preserve_keys: false))->toBe([]);
});
