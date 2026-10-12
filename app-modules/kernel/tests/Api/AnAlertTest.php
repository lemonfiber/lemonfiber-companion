<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhichWayItWent;

it('keeps the kind and which way it went as the core raised them', function (WhichWayItWent $way): void {
    $alert = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), $way, WhatTheCoreDecided::toSay('vpn.tunnel-2'));

    expect($alert->kind()->shown())->toBe('vpn.down')
        ->and($alert->way())->toBe($way);
})->with(WhichWayItWent::cases());

it('is filed under its name, so its onset and its resolution are one', function (): void {
    $onset = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, WhatTheCoreDecided::toSay('vpn.tunnel-2'));
    $resolved = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Resolved, WhatTheCoreDecided::toSay('vpn.tunnel-2'));

    expect($onset->filedUnder()->shown())->toBe('vpn.tunnel-2')
        ->and($resolved->filedUnder()->shown())->toBe('vpn.tunnel-2');
});

it('is filed under its kind where the core gave it no name', function (WhichWayItWent $way): void {
    $alert = AnAlert::unnamed(WhatTheCoreDecided::toSay('vpn.down'), $way);

    expect($alert->filedUnder()->shown())->toBe('vpn.down')
        ->and($alert->way())->toBe($way);
})->with(WhichWayItWent::cases());

it('holds the alerts of one wake in the order they were raised, and none where there were none', function (): void {
    $first = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, WhatTheCoreDecided::toSay('a'));
    $second = AnAlert::raised(WhatTheCoreDecided::toSay('disk.full'), WhichWayItWent::Resolved, WhatTheCoreDecided::toSay('b'));

    expect(iterator_to_array(TheAlerts::of($first, $second), preserve_keys: false))->toBe([$first, $second])
        ->and(iterator_to_array(TheAlerts::none(), preserve_keys: false))->toBe([]);
});
