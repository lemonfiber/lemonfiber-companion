<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;

use Closure;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeIsOneAct;
use Modules\Kernel\Api\ASettingMove;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\SettingSaysNothing;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingMoves;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;

it('holds each setting moved with what it held, what it is to hold, and what moving it costs, the spaces around them left off', function (): void {
    $move = ASettingMove::said(' vpn.provider ', ASettingValue::value('mullvad'), ASettingValue::value('pia'), ' The tunnel drops for a moment ');

    expect([$move->setting(), $move->was()->said(), $move->value()->said(), $move->consequence()])
        ->toBe(['vpn.provider', 'mullvad', 'pia', 'The tunnel drops for a moment']);
});

it('refuses a move of a setting nobody named', function (): void {
    expect(static fn(): ASettingMove => ASettingMove::said(' ', ASettingValue::nothing(), ASettingValue::nothing(), ''))->toThrow(SettingSaysNothing::class, '`setting`');
});

it('is the change that gives each setting its value, in the order the stack listed them', function (): void {
    $provider = ASettingMove::said('vpn.provider', ASettingValue::value('mullvad'), ASettingValue::value('pia'), '');
    $type = ASettingMove::said('vpn.type', ASettingValue::nothing(), ASettingValue::value('openvpn'), '');
    $moves = TheSettingMoves::these($provider, $type);

    expect(iterator_to_array($moves, preserve_keys: false))->toBe([$provider, $type])
        ->and($moves->change()->isTheSameAs(ASettingChange::toHold('vpn.provider', 'pia')->andAlso(ASettingChange::toHold('vpn.type', 'openvpn'))))->toBeTrue()
        ->and(array_keys(iterator_to_array($moves->change(), preserve_keys: true)))->toBe(['vpn.provider', 'vpn.type']);
});

it('is the change that puts each setting back where every one goes back to its default', function (): void {
    $moves = TheSettingMoves::these(
        ASettingMove::said('vpn.provider', ASettingValue::value('pia'), ASettingValue::nothing(), ''),
        ASettingMove::said('vpn.type', ASettingValue::value('openvpn'), ASettingValue::nothing(), ''),
    );

    expect($moves->change()->isTheSameAs(ASettingChange::backToDefault('vpn.provider')->andAlso(ASettingChange::backToDefault('vpn.type'))))->toBeTrue();
});

it('refuses a change that moves nothing, or that both gives values and puts settings back', function (Closure $moving, string $refused): void {
    expect($moving)->toThrow($refused);
})->with([
    'nothing moved' => [static fn(): TheSettingMoves => TheSettingMoves::these(), SettingSaysNothing::class],
    'a value, then one put back' => [static fn(): TheSettingMoves => TheSettingMoves::these(
        ASettingMove::said('vpn.provider', ASettingValue::nothing(), ASettingValue::value('pia'), ''),
        ASettingMove::said('vpn.type', ASettingValue::value('openvpn'), ASettingValue::nothing(), ''),
    ), ASettingChangeIsOneAct::class],
    'one put back, then a value' => [static fn(): TheSettingMoves => TheSettingMoves::these(
        ASettingMove::said('vpn.type', ASettingValue::value('openvpn'), ASettingValue::nothing(), ''),
        ASettingMove::said('vpn.provider', ASettingValue::nothing(), ASettingValue::value('pia'), ''),
    ), ASettingChangeIsOneAct::class],
]);

it('rehearses a change only where it was a rehearsal of that same change', function (): void {
    $moves = TheSettingMoves::these(ASettingMove::said('vpn.provider', ASettingValue::nothing(), ASettingValue::value('pia'), ''));
    $reaches = WhatTheChangeReaches::said(Services::these(ServiceId::called('gluetun')), Services::none(), Services::these(ServiceId::called('qbittorrent')), TheServicesWaiting::none(), 'gluetun was stopped');
    $settings = TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these());
    $rehearsal = TheSettingAsChanged::said($moves, $reaches, WhatTheSettingRunDid::Rehearsed, AnOffer::named('an-offer'), $settings);

    expect($rehearsal->rehearses(ASettingChange::toHold('vpn.provider', 'pia')))->toBeTrue()
        ->and($rehearsal->rehearses(ASettingChange::toHold('vpn.provider', 'mullvad')))->toBeFalse()
        ->and(TheSettingAsChanged::said($moves, $reaches, WhatTheSettingRunDid::Applied, AnOffer::named('an-offer'), $settings)->rehearses(ASettingChange::toHold('vpn.provider', 'pia')))->toBeFalse()
        ->and([$rehearsal->moves(), $rehearsal->reaches(), $rehearsal->settings(), $rehearsal->did()])->toBe([$moves, $reaches, $settings, WhatTheSettingRunDid::Rehearsed])
        ->and($rehearsal->change())->toBe($moves->change());
});

it('says what the change reaches: what it regenerates, restarts, starts and leaves waiting, and why nothing restarted', function (): void {
    $regenerates = Services::these(ServiceId::called('gluetun'));
    $restarts = Services::these(ServiceId::called('qbittorrent'));
    $starts = Services::these(ServiceId::called('caddy'));
    $waiting = TheServicesWaiting::none();
    $reaches = WhatTheChangeReaches::said($regenerates, $restarts, $starts, $waiting, 'gluetun was stopped');

    expect([$reaches->regenerates(), $reaches->restarts(), $reaches->starts(), $reaches->leavesWaiting(), $reaches->unrestarted()])
        ->toBe([$regenerates, $restarts, $starts, $waiting, 'gluetun was stopped']);
});
