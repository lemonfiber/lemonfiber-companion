<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\SomethingBeside;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;

it('N6-R7 — hands back each list as it was given, in order', function (): void {
    $root = WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes');
    $first = SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret);
    $second = SomethingKept::kept('Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain);
    $beside = SomethingBeside::named('/srv/media', 'Your library');

    $keeps = WhatThisMachineKeeps::of(TheRoots::of($root), WhatIsKept::of($first, $second), WhatIsBeside::of($beside));

    expect(iterator_to_array($keeps->roots(), preserve_keys: false))->toBe([$root])
        ->and(iterator_to_array($keeps->kept(), preserve_keys: false))->toBe([$first, $second])
        ->and(iterator_to_array($keeps->beside(), preserve_keys: false))->toBe([$beside])
        ->and($keeps->roots())->toHaveCount(1)
        ->and($keeps->kept())->toHaveCount(2)
        ->and($keeps->beside())->toHaveCount(1);
});

it('a machine that keeps nothing is an answer with three empty lists', function (): void {
    $keeps = WhatThisMachineKeeps::of(TheRoots::of(), WhatIsKept::of(), WhatIsBeside::of());

    expect([count($keeps->roots()), count($keeps->kept()), count($keeps->beside())])->toBe([0, 0, 0]);
});

it('is a list in each of its three parts however they were handed their items', function (): void {
    $root = WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes');
    $kept = SomethingKept::kept('Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain);
    $beside = SomethingBeside::named('/srv/media', 'Your library');

    expect(array_keys(iterator_to_array(TheRoots::of(...['first' => $root, 'second' => $root]), preserve_keys: true)))->toBe([0, 1])
        ->and(array_keys(iterator_to_array(WhatIsKept::of(...['first' => $kept, 'second' => $kept]), preserve_keys: true)))->toBe([0, 1])
        ->and(array_keys(iterator_to_array(WhatIsBeside::of(...['first' => $beside, 'second' => $beside]), preserve_keys: true)))->toBe([0, 1]);
});
