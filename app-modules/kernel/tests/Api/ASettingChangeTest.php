<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use Closure;

use function expect;
use function it;

use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeIsOneAct;
use Modules\Kernel\Api\ChangingASetting;
use Modules\Kernel\Api\SettingSaysNothing;

/**
 * Each setting a change moves, by its name, with the value it is to hold, null where it goes back to its default.
 *
 * @return array<string, ?string>
 */
function whatTheChangeMoves(ASettingChange $change): array
{
    $moves = [];

    foreach ($change as $setting => $value) {
        $moves[$setting] = $value->isHeld() ? $value->said() : null;
    }

    return $moves;
}

it('moves each setting with the spaces around its name left off, whichever way it changes them', function (): void {
    expect(whatTheChangeMoves(ASettingChange::toHold(' vpn.provider ', 'mullvad')->andAlso(ASettingChange::toHold(' vpn.type ', 'wireguard'))))->toBe(['vpn.provider' => 'mullvad', 'vpn.type' => 'wireguard'])
        ->and(whatTheChangeMoves(ASettingChange::backToDefault(' vpn.provider ')->andAlso(ASettingChange::backToDefault(' vpn.type '))))->toBe(['vpn.provider' => null, 'vpn.type' => null]);
});

it('carries each value exactly as typed, a blank one as a value', function (): void {
    expect(whatTheChangeMoves(ASettingChange::toHold('vpn.provider', ' mullvad ')->andAlso(ASettingChange::toHold('vpn.countries', ''))))->toBe(['vpn.provider' => ' mullvad ', 'vpn.countries' => '']);
});

it('says which act it is', function (): void {
    expect([ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard'))->how(), ASettingChange::backToDefault('vpn.provider')->andAlso(ASettingChange::backToDefault('vpn.type'))->how()])
        ->toBe([ChangingASetting::Set, ChangingASetting::Clear]);
});

it('holds the last value asked of a setting asked twice, where it was first asked', function (): void {
    expect(whatTheChangeMoves(ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard'))->andAlso(ASettingChange::toHold('vpn.provider', 'pia'))))
        ->toBe(['vpn.provider' => 'pia', 'vpn.type' => 'wireguard']);
});

it('refuses a setting nobody named', function (Closure $naming): void {
    expect($naming)->toThrow(SettingSaysNothing::class, '`setting`');
})->with([
    'given a value' => [static fn(): ASettingChange => ASettingChange::toHold('  ', 'mullvad')],
    'put back' => [static fn(): ASettingChange => ASettingChange::backToDefault('')],
]);

it('refuses giving values and putting settings back in one act', function (Closure $mixing, string $because): void {
    expect($mixing)->toThrow(ASettingChangeIsOneAct::class, $because);
})->with([
    'a value on a putting back' => [static fn(): ASettingChange => ASettingChange::backToDefault('vpn.provider')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard')), ASettingChangeIsOneAct::alreadyPuttingBack()->getMessage()],
    'a putting back on values' => [static fn(): ASettingChange => ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::backToDefault('vpn.type')), ASettingChangeIsOneAct::alreadyGivingValues()->getMessage()],
]);

it('is the same act as one moving the same settings to the same values, in whatever order they were asked', function (): void {
    expect(ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard'))->isTheSameAs(ASettingChange::toHold('vpn.type', 'wireguard')->andAlso(ASettingChange::toHold('vpn.provider', 'mullvad'))))->toBeTrue()
        ->and(ASettingChange::backToDefault('vpn.provider')->andAlso(ASettingChange::backToDefault('vpn.type'))->isTheSameAs(ASettingChange::backToDefault('vpn.type')->andAlso(ASettingChange::backToDefault('vpn.provider'))))->toBeTrue();
});

it('is not the same act as one that differs in any way', function (ASettingChange $one, ASettingChange $other): void {
    expect($one->isTheSameAs($other))->toBeFalse();
})->with([
    'another value' => [ASettingChange::toHold('vpn.provider', 'mullvad'), ASettingChange::toHold('vpn.provider', 'pia')],
    'another setting' => [ASettingChange::toHold('vpn.provider', 'mullvad'), ASettingChange::toHold('vpn.type', 'mullvad')],
    'one more setting' => [ASettingChange::toHold('vpn.provider', 'mullvad'), ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard'))],
    'one setting fewer' => [ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard')), ASettingChange::toHold('vpn.provider', 'mullvad')],
    'the other act' => [ASettingChange::toHold('vpn.provider', ''), ASettingChange::backToDefault('vpn.provider')],
]);
