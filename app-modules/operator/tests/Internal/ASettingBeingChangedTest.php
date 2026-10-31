<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\ASettingChange;
use Modules\Operator\Internal\ASettingBeingChanged;

use function sprintf;

/** What a change asks, as `settings-set a=1 b=2` or `settings-clear a b`, or nothing where it asks nothing yet. */
function whatTheOpenChangeAsks(?ASettingChange $change): string
{
    if (! $change instanceof ASettingChange) {
        return 'nothing';
    }

    $said = [$change->how()->asked()];

    foreach ($change as $setting => $value) {
        $said[] = $value->isHeld() ? sprintf('%s=%s', $setting, $value->said()) : $setting;
    }

    return implode(' ', $said);
}

it('moves each setting added alongside beside the ones already there, keeping what was chosen for each', function (): void {
    $open = new ASettingBeingChanged('vpn.provider', toDefault: false)
        ->alsoMoving('vpn.type')
        ->choosing('vpn.type', 'openvpn')
        ->alsoMoving('vpn.port')
        ->choosing('vpn.port', '1194');

    expect($open->alongside)->toBe(['vpn.type' => 'openvpn', 'vpn.port' => '1194'])
        ->and([$open->moves('vpn.provider'), $open->moves('vpn.type'), $open->moves('vpn.port'), $open->moves('vpn.region')])->toBe([true, true, true, false])
        ->and(whatTheOpenChangeAsks($open->asked('pia')))->toBe('settings-set vpn.provider=pia vpn.type=openvpn vpn.port=1194');
});

it('asks nothing while a setting moved alongside has no value chosen', function (): void {
    $open = new ASettingBeingChanged('vpn.provider', toDefault: false)->alsoMoving('vpn.type')->choosing('vpn.type', 'openvpn')->alsoMoving('vpn.port');

    expect(whatTheOpenChangeAsks($open->asked('pia')))->toBe('nothing');
});

it('leaves one setting out, keeping the rest', function (): void {
    $open = new ASettingBeingChanged('vpn.provider', toDefault: false)
        ->alsoMoving('vpn.type')->choosing('vpn.type', 'openvpn')
        ->alsoMoving('vpn.port')->choosing('vpn.port', '1194')
        ->leaving('vpn.type');

    expect($open->alongside)->toBe(['vpn.port' => '1194'])
        ->and(whatTheOpenChangeAsks($open->asked('pia')))->toBe('settings-set vpn.provider=pia vpn.port=1194');
});

it('puts back every setting moved alongside with the one opened, whatever was chosen for it', function (): void {
    $open = new ASettingBeingChanged('vpn.provider', toDefault: true)->alsoMoving('vpn.type')->alsoMoving('vpn.port');

    expect(whatTheOpenChangeAsks($open->asked('ignored')))->toBe('settings-clear vpn.provider vpn.type vpn.port')
        ->and(whatTheOpenChangeAsks(new ASettingBeingChanged('vpn.provider', toDefault: true)->asked('')))->toBe('settings-clear vpn.provider');
});
