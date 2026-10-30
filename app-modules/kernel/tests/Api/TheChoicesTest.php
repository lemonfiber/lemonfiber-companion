<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\SettingSaysNothing;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhoseASettingIs;

it('holds each value with what it needs and whether it is a choice now', function (): void {
    $needs = WhatAChoiceNeeds::holding('vpn.type', 'openvpn');
    $choice = AChoice::of('pia', $needs, open: false);

    expect([$choice->value(), $choice->needs(), $choice->isOpenNow(), AChoice::of('mullvad', WhatAChoiceNeeds::nothing(), open: true)->isOpenNow()])
        ->toBe(['pia', $needs, false, true]);
});

it('keeps what a value needs by each setting, with the spaces around its name left off', function (): void {
    expect(iterator_to_array(WhatAChoiceNeeds::holding(' vpn.type ', 'openvpn')->and(WhatAChoiceNeeds::holding('vpn.port', '1194', '1197')), preserve_keys: true))
        ->toBe(['vpn.type' => ['openvpn'], 'vpn.port' => ['1194', '1197']])
        ->and(iterator_to_array(WhatAChoiceNeeds::nothing(), preserve_keys: true))->toBe([]);
});

/** A setting nobody reads, holding nothing, by this name. */
function aSettingCalled(string $name): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
}

it('says which settings a value needs', function (): void {
    $needs = WhatAChoiceNeeds::holding('vpn.type', 'openvpn');

    expect([$needs->names(aSettingCalled('vpn.type')), $needs->names(aSettingCalled('vpn.port')), WhatAChoiceNeeds::nothing()->names(aSettingCalled('vpn.type'))])->toBe([true, false, false]);
});

it('needs what both of two needs name, the later word standing on a setting both name', function (): void {
    expect(iterator_to_array(WhatAChoiceNeeds::holding('vpn.type', 'openvpn')->and(WhatAChoiceNeeds::holding('vpn.port', '1194'))->and(WhatAChoiceNeeds::holding('vpn.type', 'wireguard')), preserve_keys: true))
        ->toBe(['vpn.type' => ['wireguard'], 'vpn.port' => ['1194']]);
});

it('refuses a need of a setting nobody named', function (): void {
    expect(static fn(): WhatAChoiceNeeds => WhatAChoiceNeeds::holding(' ', 'openvpn'))->toThrow(SettingSaysNothing::class, '`needs`');
});

it('lists its values in the stack\'s order', function (): void {
    $mullvad = AChoice::of('mullvad', WhatAChoiceNeeds::nothing(), open: true);
    $pia = AChoice::of('pia', WhatAChoiceNeeds::nothing(), open: false);
    $choices = TheChoices::these($mullvad, $pia);

    expect(iterator_to_array($choices, preserve_keys: false))->toBe([$mullvad, $pia])
        ->and([$choices->isEmpty(), TheChoices::none()->isEmpty()])->toBe([false, true]);
});

it('hangs on a setting any one of its values needs, and on no other', function (): void {
    $choices = TheChoices::these(
        AChoice::of('mullvad', WhatAChoiceNeeds::nothing(), open: true),
        AChoice::of('pia', WhatAChoiceNeeds::holding('vpn.type', 'openvpn'), open: false),
    );

    expect([$choices->hangOn(aSettingCalled('vpn.type')), $choices->hangOn(aSettingCalled('vpn.port')), TheChoices::none()->hangOn(aSettingCalled('vpn.type'))])->toBe([true, false, false]);
});
