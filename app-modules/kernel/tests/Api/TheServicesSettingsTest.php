<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
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

it('names no owner where nothing is held', function (): void {
    expect(iterator_to_array(TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these())->owners(), preserve_keys: false))->toBe([]);
});
