<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Presenters\HowWhatAServiceWaitsForReads;
use Modules\Operator\Internal\ViewModels\AStackSettingAsShown;

/** A setting of the core's nobody reads, holding nothing, by this name. */
function aSettingTheGuardWaitsOn(string $name): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
}

/** The guard held back on its type and its provider, listed in that order, beside its port and the domain. */
function theGuardWaitingOnTwo(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(aSettingTheGuardWaitsOn('domain'), aSettingTheGuardWaitsOn('vpn.type'), aSettingTheGuardWaitsOn('vpn.port'), aSettingTheGuardWaitsOn('vpn.provider')),
        TheSecretsRead::these(),
    )->holdingBack(TheServicesWaiting::these(AServiceWaiting::for(ServiceId::called('gluetun'), WhatAServiceWaitsFor::aSetting('vpn.provider'), WhatAServiceWaitsFor::aSecret('vpn.key'), WhatAServiceWaitsFor::aSetting('vpn.type'))));
}

/**
 * The settings the page offers, by their names.
 *
 * @param list<string> $alongside
 * @return list<string>
 */
function theSettingsOffered(array $alongside): array
{
    return array_map(static fn(AStackSettingAsShown $setting): string => $setting->name, new HowWhatAServiceWaitsForReads()->these(theGuardWaitingOnTwo(), ServiceId::called('gluetun'), $alongside)->settings);
}

it('offers each setting the service waits for in the order the stack lists its settings, then each moved alongside, each once and only where it is listed', function (): void {
    expect(theSettingsOffered([]))->toBe(['vpn.type', 'vpn.provider'])
        ->and(theSettingsOffered(['vpn.port', 'vpn.type', 'vpn.unlisted']))->toBe(['vpn.type', 'vpn.provider', 'vpn.port']);
});

it('says a service nothing holds back waits for nothing', function (): void {
    $shows = new HowWhatAServiceWaitsForReads()->these(theGuardWaitingOnTwo(), ServiceId::called('caddy'), []);

    expect([$shows->waiting, $shows->settings, $shows->went->cameBack()])->toBe([null, [], true]);
});
