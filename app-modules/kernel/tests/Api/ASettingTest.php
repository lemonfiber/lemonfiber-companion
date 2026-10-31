<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhoseASettingIs;

it('holds a setting under its name with the spaces around it left off', function (): void {
    expect(ASetting::read(' vpn.provider ', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing())->name())->toBe('vpn.provider');
});

it('holds no choices until it is said to be among some, then those', function (): void {
    $setting = ASetting::read('vpn.provider', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
    $choices = TheChoices::these(AChoice::of('mullvad', WhatAChoiceNeeds::nothing(), open: true));

    expect($setting->choices()->isEmpty())->toBeTrue()
        ->and($setting->among($choices)->choices())->toBe($choices)
        ->and($setting->among($choices)->name())->toBe('vpn.provider');
});

it('hangs together with a setting its choices hang on, and with one whose choices hang on it, never with itself or one apart', function (): void {
    $provider = ASetting::read('vpn.provider', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing())
        ->among(TheChoices::these(AChoice::of('pia', WhatAChoiceNeeds::holding('vpn.type', 'openvpn'), open: false)));
    $type = ASetting::read('vpn.type', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
    $domain = ASetting::read('domain', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
    $hangingOnItself = ASetting::read('vpn.port', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing())
        ->among(TheChoices::these(AChoice::of('1194', WhatAChoiceNeeds::holding('vpn.port', '1194'), open: true)));

    expect([
        $provider->hangsTogetherWith($type),
        $type->hangsTogetherWith($provider),
        $provider->hangsTogetherWith($domain),
        $domain->hangsTogetherWith($provider),
        $hangingOnItself->hangsTogetherWith($hangingOnItself),
    ])->toBe([true, true, false, false, false]);
});
