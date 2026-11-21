<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Operator\Internal\HowADecisionIsChanged;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
use Modules\Operator\Internal\ViewModels\AutostartAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAProtocolOpenedAsShown;
use Modules\Operator\Internal\ViewModels\WhatSetupDecidedAsShown;

function aRowOf(string $key, HowADecisionIsChanged $how, string $command = '', ?string $setting = null): ADecisionAsShown
{
    return new ADecisionAsShown($key, 'what it affects', '', isSet: false, costSaid: '', how: $how, goes: '', command: $command, standsSaid: '', setting: $setting);
}

it('finds the row a change is drawn under only where the screen offers it there, among the decisions and the steps', function (): void {
    $host = aRowOf('USENET_HOST', HowADecisionIsChanged::Here);
    $data = aRowOf('DATA_ROOT', HowADecisionIsChanged::Here);
    $kept = aRowOf('VPN_PROVIDER', HowADecisionIsChanged::Here, setting: 'gluetun:provider');
    $shown = new WhatSetupDecidedAsShown(
        HowTheReadingWent::itCameBack(),
        [$data, aRowOf('USENET_PASS', HowADecisionIsChanged::AtTheMachine), $kept],
        opened: new WhatAProtocolOpenedAsShown('LEMONFIBER_USENET', [aRowOf('A provider account', HowADecisionIsChanged::ToObtain), $host], proven: false),
    );

    expect($shown->offered('DATA_ROOT'))->toBe($data)
        ->and($shown->offered('USENET_HOST'))->toBe($host)
        ->and($shown->offered('USENET_PASS'))->toBeNull()
        ->and($shown->offered('VPN_PROVIDER'))->toBe($kept)
        ->and($shown->offered('A provider account'))->toBeNull()
        ->and($shown->offered('NOT_LISTED'))->toBeNull()
        ->and(new WhatSetupDecidedAsShown(HowTheReadingWent::itCameBack(), [$data])->offered('DATA_ROOT'))->toBe($data);
});

it('hands on only the commands it names, the autostart ones first', function (): void {
    $shown = new WhatSetupDecidedAsShown(
        HowTheReadingWent::itCameBack(),
        [aRowOf('INDEXER_APIKEY', HowADecisionIsChanged::AtTheMachine, 'set the key'), aRowOf('DATA_ROOT', HowADecisionIsChanged::Here)],
        new AutostartAsShown('stacks.hosting.hosted', 'install it', 'remove it'),
        new WhatAProtocolOpenedAsShown('LEMONFIBER_USENET', [aRowOf('USENET_PASS', HowADecisionIsChanged::AtTheMachine, 'set the password')], proven: false),
    );

    expect($shown->commands())->toBe(['install it', 'remove it', 'set the key', 'set the password'])
        ->and(new WhatSetupDecidedAsShown(HowTheReadingWent::itCameBack())->commands())->toBe([]);
});

it('says whether a key is one of the steps a change opened', function (): void {
    $opened = new WhatAProtocolOpenedAsShown('LEMONFIBER_USENET', [aRowOf('USENET_HOST', HowADecisionIsChanged::Here), aRowOf('USENET_PASS', HowADecisionIsChanged::AtTheMachine)], proven: true);

    expect($opened->among('USENET_HOST'))->toBeTrue()
        ->and($opened->among('USENET_PASS'))->toBeTrue()
        ->and($opened->among('DATA_ROOT'))->toBeFalse()
        ->and(new WhatAProtocolOpenedAsShown('X', [], proven: true)->among('X'))->toBeFalse();
});
