<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhatASettingFallsBackTo;
use Modules\Kernel\Api\WhereADefaultComesFrom;

it('holds the core\'s own default and a declared one by value, an empty one as empty rather than none', function (): void {
    $theCores = WhatASettingFallsBackTo::theCores('home.arpa');
    $declared = WhatASettingFallsBackTo::declared('');

    expect([$theCores->from(), $theCores->value()->isHeld(), $theCores->value()->said()])->toBe([WhereADefaultComesFrom::TheCore, true, 'home.arpa'])
        ->and([$declared->from(), $declared->value()->isHeld(), $declared->value()->said()])->toBe([WhereADefaultComesFrom::Declared, true, ''])
        ->and(iterator_to_array($theCores->readers(), preserve_keys: false))->toBe([])
        ->and(iterator_to_array($declared->readers(), preserve_keys: false))->toBe([]);
});

it('holds each reader\'s own default with no one value, and nothing declared with no value and no reader', function (): void {
    $gluetun = AServiceReadingIt::of(ServiceId::called('gluetun'), 'SERVER_COUNTRIES', ASettingValue::value('Netherlands'));
    $qbittorrent = AServiceReadingIt::of(ServiceId::called('qbittorrent'), 'COUNTRY', ASettingValue::nothing());
    $each = WhatASettingFallsBackTo::eachReadersOwn(TheServicesReadingIt::these($gluetun, $qbittorrent));
    $nothing = WhatASettingFallsBackTo::nothing();

    expect([$each->from(), $each->value()->isHeld(), iterator_to_array($each->readers(), preserve_keys: false)])->toBe([WhereADefaultComesFrom::EachReader, false, [$gluetun, $qbittorrent]])
        ->and([$nothing->from(), $nothing->value()->isHeld(), iterator_to_array($nothing->readers(), preserve_keys: false)])->toBe([WhereADefaultComesFrom::Nowhere, false, []]);
});

it('is the same as a value only where it falls back to that one value', function (): void {
    expect(WhatASettingFallsBackTo::declared('mullvad')->isTheSameAs(ASettingValue::value('mullvad')))->toBeTrue()
        ->and(WhatASettingFallsBackTo::theCores('')->isTheSameAs(ASettingValue::value('')))->toBeTrue()
        ->and(WhatASettingFallsBackTo::declared('mullvad')->isTheSameAs(ASettingValue::value('pia')))->toBeFalse()
        ->and(WhatASettingFallsBackTo::declared('')->isTheSameAs(ASettingValue::nothing()))->toBeFalse()
        ->and(WhatASettingFallsBackTo::nothing()->isTheSameAs(ASettingValue::value('')))->toBeFalse()
        ->and(WhatASettingFallsBackTo::eachReadersOwn(TheServicesReadingIt::these())->isTheSameAs(ASettingValue::value('')))->toBeFalse();
});
