<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\SettingSaysNothing;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhoseASettingIs;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What one thing waited for says of itself, folded. */
function whatItWaitsFor(WhatAServiceWaitsFor $want): string
{
    return $want->either(
        setting: static fn(string $setting, array $choices): TheWordCarriedOut => new TheWordCarriedOut(sprintf('setting %s (%s)', $setting, implode(',', $choices))),
        secret: static fn(string $secret): TheWordCarriedOut => new TheWordCarriedOut(sprintf('secret %s', $secret)),
        service: static fn(ServiceId $service): TheWordCarriedOut => new TheWordCarriedOut(sprintf('service %s', $service->named())),
    )->said;
}

it('says what it waits for, each kind with its own name and with the spaces around it left off', function (): void {
    expect(array_map(whatItWaitsFor(...), [
        WhatAServiceWaitsFor::aSetting(' vpn.provider ', 'mullvad', 'pia'),
        WhatAServiceWaitsFor::aSetting('vpn.countries'),
        WhatAServiceWaitsFor::aSecret(' vpn.key '),
        WhatAServiceWaitsFor::aService(ServiceId::called('gluetun')),
    ]))->toBe(['setting vpn.provider (mullvad,pia)', 'setting vpn.countries ()', 'secret vpn.key', 'service gluetun']);
});

it('refuses a setting or a secret nobody named', function (): void {
    expect(static fn(): WhatAServiceWaitsFor => WhatAServiceWaitsFor::aSetting(' '))->toThrow(SettingSaysNothing::class, '`waits for`')
        ->and(static fn(): WhatAServiceWaitsFor => WhatAServiceWaitsFor::aSecret(''))->toThrow(SettingSaysNothing::class, '`waits for`');
});

/** A setting nobody reads, holding nothing, by this name. */
function aSettingWaitedFor(string $name): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
}

it('holds a service with everything it waits for in the stack\'s order, and says which settings are among it', function (): void {
    $provider = WhatAServiceWaitsFor::aSetting('vpn.provider', 'mullvad');
    $key = WhatAServiceWaitsFor::aSecret('vpn.key');
    $countries = WhatAServiceWaitsFor::aSetting('vpn.countries');
    $through = WhatAServiceWaitsFor::aService(ServiceId::called('vpn.type'));
    $waiting = AServiceWaiting::for(ServiceId::called('gluetun'), $provider, $key, $countries, $through);

    expect($waiting->service()->named())->toBe('gluetun')
        ->and(iterator_to_array($waiting, preserve_keys: false))->toBe([$provider, $key, $countries, $through])
        ->and([
            $waiting->waitsFor(aSettingWaitedFor('vpn.provider')),
            $waiting->waitsFor(aSettingWaitedFor('vpn.countries')),
            $waiting->waitsFor(aSettingWaitedFor('vpn.key')),
            $waiting->waitsFor(aSettingWaitedFor('vpn.type')),
            AServiceWaiting::for(ServiceId::called('caddy'))->waitsFor(aSettingWaitedFor('vpn.provider')),
        ])->toBe([true, true, false, false, false]);
});

it('lists each service held back in the stack\'s order', function (): void {
    $gluetun = AServiceWaiting::for(ServiceId::called('gluetun'), WhatAServiceWaitsFor::aSecret('vpn.key'));
    $qbittorrent = AServiceWaiting::for(ServiceId::called('qbittorrent'), WhatAServiceWaitsFor::aService(ServiceId::called('gluetun')));
    $waiting = TheServicesWaiting::these($gluetun, $qbittorrent);

    expect(iterator_to_array($waiting, preserve_keys: false))->toBe([$gluetun, $qbittorrent])
        ->and(iterator_to_array(TheServicesWaiting::none(), preserve_keys: false))->toBe([]);
});
