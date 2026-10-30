<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingChangeWasNotRehearsed;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Tests\Support\ASettingChangeAsReported;

/** A run of this change, as the stack reported it. */
function aRunOf(ASettingChange $change, WhatTheSettingRunDid $did): TheSettingAsChanged
{
    return TheSettingAsChanged::said(ASettingChangeAsReported::moves($change, ASettingValue::nothing()), WhatTheChangeReaches::said(Services::none(), Services::none(), Services::none(), TheServicesWaiting::none(), ''), $did, AnOffer::named('an-offer'), TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these()));
}

it('agrees to the change a rehearsal described, carrying its offer back', function (): void {
    $change = ASettingChange::toHold('vpn.countries', 'Germany');
    $agreed = ASettingChangeAgreed::after(aRunOf($change, WhatTheSettingRunDid::Rehearsed), $change);

    expect($agreed->change())->toBe($change)
        ->and($agreed->offer())->toEqual(AnOffer::named('an-offer'));
});

it('refuses a yes against anything but a rehearsal of that very change', function (TheSettingAsChanged $answer, ASettingChange $change): void {
    expect(static fn(): ASettingChangeAgreed => ASettingChangeAgreed::after($answer, $change))
        ->toThrow(ASettingChangeWasNotRehearsed::class, $change->how()->asked());
})->with([
    'a run that wrote it' => [aRunOf(ASettingChange::toHold('vpn.countries', 'Germany'), WhatTheSettingRunDid::Applied), ASettingChange::toHold('vpn.countries', 'Germany')],
    'another setting' => [aRunOf(ASettingChange::toHold('domain', 'Germany'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', 'Germany')],
    'another value' => [aRunOf(ASettingChange::toHold('vpn.countries', 'France'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', 'Germany')],
    'a value where the default was rehearsed' => [aRunOf(ASettingChange::backToDefault('vpn.countries'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', '')],
    'the default where a value was rehearsed' => [aRunOf(ASettingChange::toHold('vpn.countries', ''), WhatTheSettingRunDid::Rehearsed), ASettingChange::backToDefault('vpn.countries')],
    'one more setting than was rehearsed' => [aRunOf(ASettingChange::toHold('vpn.provider', 'mullvad'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard'))],
    'one setting fewer than was rehearsed' => [aRunOf(ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard')), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.provider', 'mullvad')],
]);

it('agrees to a change moving the settings a rehearsal moved, asked in another order', function (): void {
    $change = ASettingChange::toHold('vpn.type', 'wireguard')->andAlso(ASettingChange::toHold('vpn.provider', 'mullvad'));
    $agreed = ASettingChangeAgreed::after(aRunOf(ASettingChange::toHold('vpn.provider', 'mullvad')->andAlso(ASettingChange::toHold('vpn.type', 'wireguard')), WhatTheSettingRunDid::Rehearsed), $change);

    expect($agreed->change())->toBe($change);
});
