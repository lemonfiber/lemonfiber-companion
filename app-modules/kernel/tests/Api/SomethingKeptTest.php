<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;

it('N6-R7 — keeps what it is, where, why, and whether it holds a secret', function (): void {
    $kept = SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret);

    expect([$kept->what(), $kept->where(), $kept->why(), $kept->secret()])
        ->toBe(['The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret]);
});

it('N6-R7 — refuses an entry with any of its three words blank, naming the one', function (): void {
    expect(fn(): SomethingKept => SomethingKept::kept(' ', '/srv', 'why', WhetherItHoldsASecret::Plain))->toThrow(KeepingSaysNothing::class, '`what`')
        ->and(fn(): SomethingKept => SomethingKept::kept('what', '', 'why', WhetherItHoldsASecret::Plain))->toThrow(KeepingSaysNothing::class, '`at`')
        ->and(fn(): SomethingKept => SomethingKept::kept('what', '/srv', "\t", WhetherItHoldsASecret::Plain))->toThrow(KeepingSaysNothing::class, '`why`');
});
