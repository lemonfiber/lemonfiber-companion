<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;

/** What the stack holds: one setting and one secret read by `gluetun`, one of each read by `core`, and one of each read by nobody. */
function whatTheStackHolds(): TheServicesSettings
{
    $readBy = static fn(string ...$services): TheServicesReadingIt => TheServicesReadingIt::these(
        ...array_map(static fn(string $service): AServiceReadingIt => AServiceReadingIt::of(ServiceId::called($service), 'A_VARIABLE', ASettingValue::nothing()), $services),
    );

    return TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('gluetun.countries', WhoseASettingIs::theNamespaces('gluetun'), $readBy('gluetun'), ASettingValue::value('NL')),
            ASetting::read('LEMONFIBER_DOMAIN', WhoseASettingIs::theCores(), $readBy('core'), ASettingValue::nothing()),
            ASetting::read('LEMONFIBER_UNREAD', WhoseASettingIs::theCores(), $readBy(), ASettingValue::nothing()),
        ),
        TheSecretsRead::these(
            ASecret::read('gluetun.key', WhoseASettingIs::theNamespaces('gluetun'), $readBy('gluetun'), held: true, from: WhereASecretCameFrom::Operator),
            ASecret::read('LEMONFIBER_TOKEN', WhoseASettingIs::theCores(), $readBy('core', 'caddy'), held: true, from: WhereASecretCameFrom::Minted),
            ASecret::read('LEMONFIBER_SPARE', WhoseASettingIs::theCores(), $readBy(), held: false, from: WhereASecretCameFrom::Unsaid),
        ),
    );
}

/**
 * The names of these, in order.
 *
 * @param iterable<ASetting|ASecret> $read
 *
 * @return list<string>
 */
function theNamesOf(iterable $read): array
{
    return array_map(static fn(ASetting|ASecret $one): string => $one->name(), iterator_to_array($read, preserve_keys: false));
}

it('keeps only what one of these services reads', function (): void {
    $read = whatTheStackHolds()->readByAnyOf(Services::these(ServiceId::called('gluetun')));

    expect(theNamesOf($read->settings()))->toBe(['gluetun.countries'])
        ->and(theNamesOf($read->secrets()))->toBe(['gluetun.key']);
});

it('keeps only what none of these services reads, what no service reads among it', function (): void {
    $read = whatTheStackHolds()->readByNoneOf(Services::these(ServiceId::called('gluetun')));

    expect(theNamesOf($read->settings()))->toBe(['LEMONFIBER_DOMAIN', 'LEMONFIBER_UNREAD'])
        ->and(theNamesOf($read->secrets()))->toBe(['LEMONFIBER_TOKEN', 'LEMONFIBER_SPARE']);
});

it('keeps everything where it is asked of no service', function (): void {
    $read = whatTheStackHolds()->readByNoneOf(Services::none());

    expect(theNamesOf($read->settings()))->toHaveCount(3)
        ->and(theNamesOf($read->secrets()))->toHaveCount(3);
});

it('leaves out a secret one of the services reads beside a service that is not among them', function (): void {
    $read = whatTheStackHolds()->readByNoneOf(Services::these(ServiceId::called('caddy')));

    expect(theNamesOf($read->secrets()))->toBe(['gluetun.key', 'LEMONFIBER_SPARE']);
});

it('keeps only what one owner holds', function (): void {
    $read = whatTheStackHolds()->ownedBy(WhoseASettingIs::theNamespaces('gluetun'));

    expect(theNamesOf($read->settings()))->toBe(['gluetun.countries'])
        ->and(theNamesOf($read->secrets()))->toBe(['gluetun.key']);
});

it('names each owner once, the core first, then each namespace in the order it is first listed', function (): void {
    $owners = iterator_to_array(whatTheStackHolds()->owners(), preserve_keys: false);

    expect($owners)->toHaveCount(2)
        ->and($owners[0]->is(WhoseASettingIs::theCores()))->toBeTrue()
        ->and($owners[1]->is(WhoseASettingIs::theNamespaces('gluetun')))->toBeTrue();
});

it('names each namespace after the ones listed before it', function (): void {
    $nobody = TheServicesReadingIt::these();
    $held = TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('gluetun.countries', WhoseASettingIs::theNamespaces('gluetun'), $nobody, ASettingValue::value('NL')),
            ASetting::read('LEMONFIBER_DOMAIN', WhoseASettingIs::theCores(), $nobody, ASettingValue::nothing()),
            ASetting::read('sonarr.root', WhoseASettingIs::theNamespaces('sonarr'), $nobody, ASettingValue::value('/tv')),
        ),
        TheSecretsRead::these(),
    );
    $owners = iterator_to_array($held->owners(), preserve_keys: false);

    expect($owners)->toHaveCount(3)
        ->and($owners[0]->is(WhoseASettingIs::theCores()))->toBeTrue()
        ->and($owners[1]->is(WhoseASettingIs::theNamespaces('gluetun')))->toBeTrue()
        ->and($owners[2]->is(WhoseASettingIs::theNamespaces('sonarr')))->toBeTrue();
});

it('names no owner where nothing is held', function (): void {
    expect(iterator_to_array(TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these())->owners(), preserve_keys: false))->toBe([]);
});

it('holds back nothing until it is said to, then keeps what it holds back through every narrowing', function (): void {
    $waiting = TheServicesWaiting::these(AServiceWaiting::for(ServiceId::called('gluetun'), WhatAServiceWaitsFor::aSecret('gluetun.key')));
    $held = whatTheStackHolds()->holdingBack($waiting);

    expect(iterator_to_array(whatTheStackHolds()->waiting(), preserve_keys: false))->toBe([])
        ->and($held->waiting())->toBe($waiting)
        ->and(theNamesOf($held->settings()))->toBe(theNamesOf(whatTheStackHolds()->settings()))
        ->and($held->readByAnyOf(Services::these(ServiceId::called('core')))->waiting())->toBe($waiting)
        ->and($held->readByNoneOf(Services::these(ServiceId::called('core')))->waiting())->toBe($waiting)
        ->and($held->ownedBy(WhoseASettingIs::theCores())->waiting())->toBe($waiting);
});

it('names each setting that hangs together with one of these, apart from these, in the order it holds them', function (): void {
    $nobody = TheServicesReadingIt::these();
    $needingTheType = static fn(string $value): TheChoices => TheChoices::these(AChoice::of($value, WhatAChoiceNeeds::holding('vpn.type', 'openvpn'), open: false));
    $provider = ASetting::read('vpn.provider', WhoseASettingIs::theCores(), $nobody, ASettingValue::nothing())->among($needingTheType('pia'));
    $domain = ASetting::read('domain', WhoseASettingIs::theCores(), $nobody, ASettingValue::nothing());
    $type = ASetting::read('vpn.type', WhoseASettingIs::theCores(), $nobody, ASettingValue::nothing());
    $port = ASetting::read('vpn.port', WhoseASettingIs::theCores(), $nobody, ASettingValue::nothing())->among($needingTheType('1194'));
    $settings = TheSettingsRead::these($provider, $domain, $type, $port);

    expect(theNamesOf($settings->hangingTogetherWith($type)))->toBe(['vpn.provider', 'vpn.port'])
        ->and(theNamesOf($settings->hangingTogetherWith($provider)))->toBe(['vpn.type'])
        ->and(theNamesOf($settings->hangingTogetherWith($provider, $type)))->toBe(['vpn.port'])
        ->and(theNamesOf($settings->hangingTogetherWith($domain)))->toBe([])
        ->and(theNamesOf($settings->hangingTogetherWith()))->toBe([])
        ->and(theNamesOf($settings->hangingTogetherWith(ASetting::read('vpn.type', WhoseASettingIs::theCores(), $nobody, ASettingValue::value('wireguard')))))->toBe(['vpn.provider', 'vpn.port']);
});
