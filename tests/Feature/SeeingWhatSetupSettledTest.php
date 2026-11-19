<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowFarSetupGot;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\TheDataRoot;
use Modules\Kernel\Api\TheServiceUser;
use Modules\Kernel\Api\TheWaysOfDownloading;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Kernel\Api\WhereTheServiceUserComesFrom;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackWasSetUpWith;
use Native\Mobile\Edge\CallbackRegistry;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\AStackThatSaysWhatSetupSettled;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheSettingsOfTheLoft;
use Tests\Support\WhatPullingDownCalls;
use Tests\Support\WhatTheDeviceWouldDraw;

/** What setup settled part-way through, with no data root chosen and Usenet alone. */
function aSetupPartWay(HowFarSetupGot $setup = HowFarSetupGot::PartWay): WhatSetupSettled
{
    return WhatSetupSettled::said(
        $setup,
        TheDataRoot::notChosen(),
        TheWaysOfDownloading::of(usenet: true, torrent: false),
        TheServiceUser::runsAs(1234, 100, WhereTheServiceUserComesFrom::PartSet),
    );
}

/**
 * Every line drawn after the first that says this one, or none where nothing says it.
 *
 * @param  list<string> $said
 * @return list<string>
 */
function theLinesAfter(string $line, array $said): array
{
    $at = array_search($line, $said, strict: true);

    return $at === false ? [] : array_slice($said, $at + 1);
}

/** The screen of its own, on a stack this device is signed in to. */
function theSetUpWithScreen(AStackThatSaysWhatSetupSettled $settled, bool $signedIn = true): WhatThisStackWasSetUpWith
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisStackWasSetUpWith(
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $settled,
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('says on the settings, above the listing and on the frame after it, what setup settled, and that changing one is reconfiguring', function (): void {
    $settled = AStackThatSaysWhatSetupSettled::setUp();
    $screen = TheSettingsOfTheLoft::screen(AStackThatIsSet::to(Settings::of(Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media'), WhoPutItThere::bundled()))), settled: $settled);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('settled.reading'))
        ->and($settled->askings())->toBe(0);

    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($settled->askings())->toBe(1)
        ->and(array_slice(theLinesAfter(Catalogue::words()->for('settled.section'), $said), 0, 7))->toBe([
            __('settled.data_root'), '/srv/media',
            __('settled.downloads'), __('settled.downloading.both'),
            __('settled.runs_as'), '1000:1000',
            __('settled.runs_as_from.stack-owner'),
        ])
        ->and($said)->toContain(__('settled.reconfiguring'))
        ->and($said)->not->toContain(__('settled.unfinished'))
        ->and(theLinesAfter(Catalogue::words()->for('settled.section'), $said))->toContain('LIBRARY_PATH');
});

it('reads what setup settled again on the settings after asking again, and after a change is agreed to', function (): void {
    $settled = AStackThatSaysWhatSetupSettled::setUp();
    $screen = TheSettingsOfTheLoft::screen(AStackThatIsSet::to(Settings::of(Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media'), WhoPutItThere::bundled()))), settled: $settled);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    $screen->again();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($settled->askings())->toBe(2);

    $screen->change('LIBRARY_PATH');
    $screen->agree('LIBRARY_PATH');
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($settled->askings())->toBe(3);
});

it('shows the values setup left part-way or never began, says to finish it at the machine, and offers no setup', function (HowFarSetupGot $setup): void {
    $drawn = WhatTheDeviceWouldDraw::by(theSetUpWithScreen(AStackThatSaysWhatSetupSettled::with(aSetupPartWay($setup))));

    expect($drawn->said())->toContain(
        __('settled.unfinished'),
        __('settled.no_data_root'),
        __('settled.downloading.usenet'),
        '1234:100',
        __('settled.runs_as_from.part-set'),
    )->and($drawn->offers())->toBe([__('config.ask_again')]);
})->with([
    'part-way' => [HowFarSetupGot::PartWay],
    'not begun' => [HowFarSetupGot::None],
]);

it('says each way of downloading and where each service user comes from in its own words', function (TheWaysOfDownloading $ways, string $downloading, WhereTheServiceUserComesFrom $from): void {
    $said = WhatTheDeviceWouldDraw::by(theSetUpWithScreen(AStackThatSaysWhatSetupSettled::with(WhatSetupSettled::said(
        HowFarSetupGot::Done,
        TheDataRoot::at('nas:/export/media'),
        $ways,
        TheServiceUser::runsAs(0, 0, $from),
    ))))->said();

    expect($said)->toContain(__($downloading), __($from->saidOnTheScreen()), 'nas:/export/media', '0:0');
})->with([
    'torrents, set' => [TheWaysOfDownloading::of(usenet: false, torrent: true), 'settled.downloading.torrent', WhereTheServiceUserComesFrom::Set],
    'neither, stack owner' => [TheWaysOfDownloading::of(usenet: false, torrent: false), 'settled.downloading.neither', WhereTheServiceUserComesFrom::StackOwner],
    'both, part set' => [TheWaysOfDownloading::of(usenet: true, torrent: true), 'settled.downloading.both', WhereTheServiceUserComesFrom::PartSet],
]);

it('reads its own screen on open, and again when pulled down, with no timer', function (): void {
    $settled = AStackThatSaysWhatSetupSettled::setUp();
    $screen = theSetUpWithScreen($settled);

    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($settled->askings())->toBe(1);

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($settled->askings())->toBe(2)
        ->and(WhatPullingDownCalls::on($screen))->toBe([new CallbackRegistry()->register('again()')]);
});

it('says what stood in the way on its own screen, and that it could not be read on the settings', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $own = WhatTheDeviceWouldDraw::by(theSetUpWithScreen(AStackThatSaysWhatSetupSettled::met($why)))->said();
    $settings = WhatTheDeviceWouldDraw::onTheSecondFrame(TheSettingsOfTheLoft::screen(AStackThatIsSet::to(Settings::of()), settled: AStackThatSaysWhatSetupSettled::met($why)))->said();

    expect($own)->toContain(__($why->kind()->said()))
        ->and($own)->not->toContain(__('settled.section'))
        ->and($settings)->toContain(__('settled.unread'));
});

it('says the session ended rather than asking, where this device holds none', function (): void {
    $settled = AStackThatSaysWhatSetupSettled::setUp();
    $said = WhatTheDeviceWouldDraw::by(theSetUpWithScreen($settled, signedIn: false))->said();

    expect($settled->askings())->toBe(0)
        ->and($said)->not->toContain(__('settled.section'));
});
