<?php

declare(strict_types=1);

use Lemonfiber\Native\AppsSettings;
use Modules\Device\Api\PlatformAppsSettings;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhetherTheSettingsOpened;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\AppsSettingsThatOpen;

// The TheAppsSettings contract, run against the adapter and against the fake.
//
// `NetworkingContractTest`'s shape: the adapter arm runs over a bridge scripted
// into `FakeBridge`. What an absent bridge or an unknown word means is
// `AppsSettingsTest`'s.

/** The adapter, over a bridge scripted to answer one way. */
function overASettingsPageThatSays(string $outcome): PlatformAppsSettings
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Settings.Open', ['outcome' => $outcome]);

    return new PlatformAppsSettings(new AppsSettings());
}

/** One word carried out of either arm. */
final readonly class WhatOpeningTheSettingsCameTo
{
    public function __construct(public string $said) {}
}

/** What asking came to, as a word. */
function whatOpeningTheSettingsCameTo(WhetherTheSettingsOpened $opened): string
{
    return $opened->either(
        opened: static fn(): WhatOpeningTheSettingsCameTo => new WhatOpeningTheSettingsCameTo('opened'),
        wouldNot: static fn(): WhatOpeningTheSettingsCameTo => new WhatOpeningTheSettingsCameTo('would not'),
    )->said;
}

/** @return array<string, array{Closure(): TheAppsSettings}> */
dataset('every phone that opens the app\'s settings', [
    'the platform' => [fn(): TheAppsSettings => overASettingsPageThatSays('opened')],
    'the fake' => [fn(): TheAppsSettings => new AppsSettingsThatOpen()],
]);

/** @return array<string, array{Closure(): TheAppsSettings}> */
dataset('every phone that would not open them', [
    'the platform' => [fn(): TheAppsSettings => overASettingsPageThatSays('refused')],
    'the fake' => [fn(): TheAppsSettings => AppsSettingsThatOpen::wouldNot()],
]);

it('says the page opened where the phone opened it', function (TheAppsSettings $settings): void {
    expect(whatOpeningTheSettingsCameTo($settings->open()))->toBe('opened');
})->with('every phone that opens the app\'s settings');

it('says the page would not open where the phone would not', function (TheAppsSettings $settings): void {
    expect(whatOpeningTheSettingsCameTo($settings->open()))->toBe('would not');
})->with('every phone that would not open them');

it('asks lemonfiber\'s own call for the page', function (): void {
    FakeBridge::disable();
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Settings.Open', ['outcome' => 'opened']);

    new PlatformAppsSettings(new AppsSettings())->open();

    $bridge->assertCalled('Lemonfiber.Settings.Open');
});

it('the fake counts every asking', function (): void {
    $settings = new AppsSettingsThatOpen();
    $settings->open();
    $settings->open();

    expect($settings->timesOpened())->toBe(2);
});
