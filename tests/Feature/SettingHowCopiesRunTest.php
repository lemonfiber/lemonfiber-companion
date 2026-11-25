<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCopySettings;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Screens\HowCopiesRun;
use Modules\Stacks\Api\AStacksScreen;
use Tests\Support\AroundThePhone;
use Tests\Support\ASettingChangeAsReported;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The machine whose copies are set to run. */
function theStackWhoseCopiesAreSet(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('m', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.47:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A core setting no service reads, holding this or nothing. */
function aCoreSettingOfTheCopies(string $name, ASettingValue $value): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theCores(), TheServicesReadingIt::these(), $value);
}

/** What the stack lists: the stalled minutes set, the backup limit at its default, and one other core setting. */
function whatTheStackSetsCopiesTo(string $stalled = '12'): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            aCoreSettingOfTheCopies('LEMONFIBER_DASHBOARD_SEARCH', ASettingValue::value('https://search.example.org/?q=')),
            aCoreSettingOfTheCopies(TheCopySettings::BackupLimit->value, ASettingValue::nothing()),
            aCoreSettingOfTheCopies(TheCopySettings::StalledAfter->value, ASettingValue::value($stalled)),
        ),
        TheSecretsRead::these(),
    );
}

/** What a change to the stalled minutes came to, as the stack reported it. */
function aChangeToTheStalledMinutes(WhatTheSettingRunDid $did): TheSettingAsChanged
{
    return TheSettingAsChanged::said(
        ASettingChangeAsReported::moves(ASettingChange::toHold(TheCopySettings::StalledAfter->value, '20'), ASettingValue::value('12')),
        WhatTheChangeReaches::said(Services::none(), Services::none(), Services::none(), TheServicesWaiting::none(), ''),
        $did,
        AnOffer::named('settings-set:LEMONFIBER_COPY_STALLED_MINUTES:1'),
        whatTheStackSetsCopiesTo($did === WhatTheSettingRunDid::Applied ? '20' : '12'),
    );
}

function aStackThatSetsHowCopiesRun(): AStackThatTunesItsSettings
{
    return AStackThatTunesItsSettings::answering(
        whatTheStackSetsCopiesTo(),
        WhatBecameOfTheWork::answered(aChangeToTheStalledMinutes(WhatTheSettingRunDid::Rehearsed)),
        WhatBecameOfTheWork::answered(aChangeToTheStalledMinutes(WhatTheSettingRunDid::Applied)),
    );
}

function howCopiesRunOpened(AStackThatTunesItsSettings $tuning, bool $signedIn = true): HowCopiesRun
{
    $stack = theStackWhoseCopiesAreSet();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowCopiesRun($tuning, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), AJobThatSays::nothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the two settings for a running copy, one set with its value and one at the stack\'s default, and no other', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(howCopiesRunOpened(aStackThatSetsHowCopiesRun()))->said();

    expect($drawn)->toContain(TheCopySettings::BackupLimit->value, TheCopySettings::StalledAfter->value, __('plugins.settings.set_by_you'), '12', __('plugins.settings.default'), __('stacks.copy.settings.of'))
        ->and($drawn)->not->toContain('LEMONFIBER_DASHBOARD_SEARCH', 'https://search.example.org/?q=');
});

it('says the default\'s value only where the stack sends one, and otherwise only that it is the stack\'s default', function (): void {
    $sent = AStackThatTunesItsSettings::answering(
        TheServicesSettings::of(
            TheSettingsRead::these(ASetting::read(
                TheCopySettings::StalledAfter->value,
                WhoseASettingIs::theCores(),
                TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('core'), 'LEMONFIBER_COPY_STALLED_MINUTES', ASettingValue::value('5'))),
                ASettingValue::nothing(),
            )),
            TheSecretsRead::these(),
        ),
        WhatBecameOfTheWork::noOutcome(),
        WhatBecameOfTheWork::noOutcome(),
    );
    $withADefault = WhatTheDeviceWouldDraw::by(howCopiesRunOpened($sent))->said();
    $withNone = WhatTheDeviceWouldDraw::by(howCopiesRunOpened(aStackThatSetsHowCopiesRun()))->said();

    expect($withADefault)->toContain(__('plugins.settings.default'), __('plugins.settings.read_by_default', ['service' => 'core', 'variable' => 'LEMONFIBER_COPY_STALLED_MINUTES', 'default' => '5']))
        ->and($withNone)->toContain(__('plugins.settings.default'), __('plugins.settings.read_by_none'))
        ->and(implode("\n", $withNone))->not->toContain(' by default');
});

it('says a stack that lists neither setting does not have them', function (): void {
    $tuning = AStackThatTunesItsSettings::answering(TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these()), WhatBecameOfTheWork::noOutcome(), WhatBecameOfTheWork::noOutcome());

    expect(WhatTheDeviceWouldDraw::by(howCopiesRunOpened($tuning))->said())->toContain(__('stacks.copy.settings.none'));
});

it('changes either of the two, rehearsed and then written, and opens no other setting', function (): void {
    $tuning = aStackThatSetsHowCopiesRun();
    $screen = howCopiesRunOpened($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->change('LEMONFIBER_DASHBOARD_SEARCH');

    expect($screen->changing)->toBeNull();

    $screen->change(TheCopySettings::StalledAfter->value);
    $screen->typed = '20';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->agree();
    $screen->whileItRuns();

    expect(array_map(ASettingChangeAsReported::said(...), $tuning->rehearsed()))->toBe(['settings-set LEMONFIBER_COPY_STALLED_MINUTES=20'])
        ->and($tuning->carriedOut())->toHaveCount(1)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('20');
});

it('reads the settings once, and afresh when asked again', function (): void {
    $tuning = aStackThatSetsHowCopiesRun();
    $screen = howCopiesRunOpened($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $once = $tuning->askings();
    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($once)->toBe(1)
        ->and($tuning->askings())->toBe(2);
});

it('says what stood in the way of reading the settings, and asks nothing without a session', function (): void {
    $refused = AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), WhatBecameOfTheWork::noOutcome());
    $unasked = aStackThatSetsHowCopiesRun();
    WhatTheDeviceWouldDraw::by(howCopiesRunOpened($unasked, signedIn: false));

    expect(WhatTheDeviceWouldDraw::by(howCopiesRunOpened($refused))->said())->toContain(__(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)->said()))
        ->and($unasked->askings())->toBe(0);
});

it('is the screen its route names', function (): void {
    expect(AStacksScreen::CopiesRun->forTheStack(theStackWhoseCopiesAreSet()->id()))->toBe(sprintf('/stacks/%s/copy/settings', theStackWhoseCopiesAreSet()->id()->stored()));
});
