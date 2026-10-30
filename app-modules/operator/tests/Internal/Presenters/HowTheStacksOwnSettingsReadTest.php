<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Presenters\HowTheStacksOwnSettingsRead;
use Modules\Operator\Internal\ViewModels\AnOwnersSettingsAsShown;
use Modules\Operator\Internal\ViewModels\ASecretAsShown;
use Modules\Operator\Internal\ViewModels\AStackSettingAsShown;

/** Read by this one service, into a variable of its name. */
function readByOne(string $service): TheServicesReadingIt
{
    return TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called($service), $service, ASettingValue::nothing()));
}

/** A namespace's setting listed before the core's, a secret the guard reads, and one of each read by nobody. */
function whatTheMachineLists(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('caddy.email', WhoseASettingIs::theNamespaces('caddy'), readByOne('caddy'), ASettingValue::nothing()),
            ASetting::read('domain', WhoseASettingIs::theCores(), readByOne('caddy'), ASettingValue::nothing()),
            ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), readByOne('gluetun'), ASettingValue::nothing()),
        ),
        TheSecretsRead::these(
            ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), readByOne('gluetun'), held: true, from: WhereASecretCameFrom::Operator),
            ASecret::read('spare.key', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), held: false, from: WhereASecretCameFrom::Unsaid),
        ),
    );
}

it('groups what no installed plugin reads by whose it is, the core\'s first, and leaves each row\'s owner unsaid', function (): void {
    $shown = new HowTheStacksOwnSettingsRead()->these(whatTheMachineLists(), Services::these(ServiceId::called('gluetun')));

    expect($shown->went->cameBack())->toBeTrue()
        ->and($shown->waits)->toBeFalse()
        ->and(array_map(static fn(AnOwnersSettingsAsShown $owner): string => $owner->whose->key, $shown->owners))->toBe(['plugins.settings.the_cores', 'plugins.settings.a_namespaces'])
        ->and($shown->owners[1]->whose->filling)->toBe(['namespace' => 'caddy'])
        ->and(array_map(static fn(AStackSettingAsShown $setting): string => $setting->name, $shown->owners[0]->settings))->toBe(['domain'])
        ->and(array_map(static fn(ASecretAsShown $secret): string => $secret->name, $shown->owners[0]->secrets))->toBe(['spare.key'])
        ->and(array_map(static fn(AStackSettingAsShown $setting): string => $setting->name, $shown->owners[1]->settings))->toBe(['caddy.email'])
        ->and($shown->owners[1]->secrets)->toBe([])
        ->and($shown->owners[0]->settings[0]->whose)->toBeNull()
        ->and($shown->owners[1]->settings[0]->whose)->toBeNull();
});

it('groups nothing where an installed plugin reads everything', function (): void {
    expect(new HowTheStacksOwnSettingsRead()->these(whatTheMachineLists(), Services::these(ServiceId::called('gluetun'), ServiceId::called('caddy')))->owners)->toHaveCount(1);
});

it('waits for a frame of its own, signs out, or says what stood in the way, with nothing to group', function (): void {
    $waits = new HowTheStacksOwnSettingsRead()->waits();
    $signedOut = new HowTheStacksOwnSettingsRead()->signedOut();
    $met = new HowTheStacksOwnSettingsRead()->met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));

    expect($waits->waits)->toBeTrue()
        ->and($waits->went->cameBack())->toBeTrue()
        ->and($waits->owners)->toBe([])
        ->and($signedOut->waits)->toBeFalse()
        ->and($signedOut->went->isSignedIn)->toBeFalse()
        ->and($met->waits)->toBeFalse()
        ->and($met->went->cameBack())->toBeFalse()
        ->and($met->owners)->toBe([]);
});
