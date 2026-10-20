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
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;

/** A run of one change, as the stack reported it. */
function aRunOfOneChange(ASettingChange $change, WhatTheSettingRunDid $did): TheSettingAsChanged
{
    return TheSettingAsChanged::said($change, ASettingValue::nothing(), WhatTheChangeReaches::said(Services::none(), Services::none(), '', ''), $did, AnOffer::named('an-offer'), TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these()));
}

it('agrees to the change a rehearsal described, carrying its offer back', function (): void {
    $change = ASettingChange::toHold('vpn.countries', 'Germany');
    $agreed = ASettingChangeAgreed::after(aRunOfOneChange($change, WhatTheSettingRunDid::Rehearsed), $change);

    expect($agreed->change())->toBe($change)
        ->and($agreed->offer())->toEqual(AnOffer::named('an-offer'));
});

it('refuses a yes against anything but a rehearsal of that very change', function (TheSettingAsChanged $answer, ASettingChange $change): void {
    expect(static fn(): ASettingChangeAgreed => ASettingChangeAgreed::after($answer, $change))
        ->toThrow(ASettingChangeWasNotRehearsed::class, $change->how()->asked());
})->with([
    'a run that wrote it' => [aRunOfOneChange(ASettingChange::toHold('vpn.countries', 'Germany'), WhatTheSettingRunDid::Applied), ASettingChange::toHold('vpn.countries', 'Germany')],
    'another setting' => [aRunOfOneChange(ASettingChange::toHold('domain', 'Germany'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', 'Germany')],
    'another value' => [aRunOfOneChange(ASettingChange::toHold('vpn.countries', 'France'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', 'Germany')],
    'a value where the default was rehearsed' => [aRunOfOneChange(ASettingChange::backToDefault('vpn.countries'), WhatTheSettingRunDid::Rehearsed), ASettingChange::toHold('vpn.countries', '')],
    'the default where a value was rehearsed' => [aRunOfOneChange(ASettingChange::toHold('vpn.countries', ''), WhatTheSettingRunDid::Rehearsed), ASettingChange::backToDefault('vpn.countries')],
]);
