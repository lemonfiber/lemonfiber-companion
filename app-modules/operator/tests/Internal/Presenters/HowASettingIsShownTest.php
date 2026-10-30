<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Presenters\HowASettingIsShown;
use Modules\Operator\Internal\ViewModels\ALineToSay;

/** The proxy's domain, the core's, read by the proxy with a default of its own, set to `home.example.org`. */
function theDomainSetting(): ASetting
{
    return ASetting::read('domain', WhoseASettingIs::theCores(), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('caddy'), 'DOMAIN', ASettingValue::value('localhost'))), ASettingValue::value('home.example.org'));
}

/** The guard's countries, its own, read by nobody and left as it came. */
function theCountriesSetting(): ASetting
{
    return ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(), ASettingValue::nothing());
}

it('says whose each setting is, except the owner the screen already names', function (): void {
    $rows = new HowASettingIsShown(WhoseASettingIs::theNamespaces('gluetun'))->settings(TheSettingsRead::these(theDomainSetting(), theCountriesSetting()));

    expect($rows[0]->whose)->toEqual(new ALineToSay('plugins.settings.the_cores', []))
        ->and($rows[1]->whose)->toBeNull()
        ->and(new HowASettingIsShown(WhoseASettingIs::theCores())->settings(TheSettingsRead::these(theDomainSetting()))[0]->whose)->toBeNull();
});

it('draws a setting by its name, whether and to what the operator set it, and each service reading it', function (): void {
    [$domain, $countries] = new HowASettingIsShown(WhoseASettingIs::theCores())->settings(TheSettingsRead::these(theDomainSetting(), theCountriesSetting()));

    expect($domain->name)->toBe('domain')
        ->and($domain->isSet)->toBeTrue()
        ->and($domain->value)->toBe('home.example.org')
        ->and($domain->readers)->toHaveCount(1)
        ->and($domain->readers[0]->service)->toBe('caddy')
        ->and($domain->readers[0]->variable)->toBe('DOMAIN')
        ->and($domain->readers[0]->declaresADefault)->toBeTrue()
        ->and($domain->readers[0]->default)->toBe('localhost')
        ->and($countries->isSet)->toBeFalse()
        ->and($countries->value)->toBe('')
        ->and($countries->readers)->toBe([]);
});

it('draws a secret by whether a value is held and where it came from, and never by the value', function (): void {
    $rows = new HowASettingIsShown(WhoseASettingIs::theCores())->secrets(TheSecretsRead::these(
        ASecret::read('proxy.token', WhoseASettingIs::theCores(), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('caddy'), 'TOKEN', ASettingValue::nothing())), held: true, from: WhereASecretCameFrom::Minted),
        ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(), held: false, from: WhereASecretCameFrom::Unsaid),
    ));

    expect($rows[0]->name)->toBe('proxy.token')
        ->and($rows[0]->whose)->toBeNull()
        ->and($rows[0]->heldSaid)->toBe('plugins.settings.secret_held')
        ->and($rows[0]->fromSaid)->toBe(WhereASecretCameFrom::Minted->saidOnTheScreen())
        ->and($rows[0]->readers[0]->variable)->toBe('TOKEN')
        ->and($rows[1]->whose)->toEqual(new ALineToSay('plugins.settings.a_namespaces', ['namespace' => 'gluetun']))
        ->and($rows[1]->heldSaid)->toBe('plugins.settings.secret_unset')
        ->and($rows[1]->fromSaid)->toBe(WhereASecretCameFrom::Unsaid->saidOnTheScreen());
});
