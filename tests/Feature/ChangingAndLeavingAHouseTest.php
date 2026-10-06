<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\YourCornerOfTheHouse;
use Modules\Household\Internal\ViewModels\AHouseToChooseAsShown;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\AMemberOnTheirProfile;
use Tests\Support\WhatMarkupDraws;
use Tests\Support\WhatTheDeviceWouldDraw;

// A member's Profile: Switch house lists the houses this phone holds by name
// alone, App settings opens in household words, and taking the house off the
// phone is asked on the tab and lands where the app goes once it is gone.

/** A house on this phone. Named for this file. */
function aHouseOnTheirProfile(string $seed, string $name): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($name),
        Address::of('https://192.168.1.48'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** Where Profile sent the app, as how and where. Named for this file. */
function whereProfileWent(YourCornerOfTheHouse $screen): string
{
    $intent = $screen->getNavigationIntent();

    return $intent instanceof NavigationIntent ? sprintf('%s %s', $intent->type, $intent->uri ?? '') : 'nowhere';
}

it('is served at the house\'s own path, titled Profile, and offers its three rows', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');
    $drawn = WhatTheDeviceWouldDraw::by(new AMemberOnTheirProfile($home)->profileOf($home));

    expect(data_get(NativeRouter::resolve(AStacksScreen::Profile->forTheStack($home->id())), 'class'))->toBe(YourCornerOfTheHouse::class)
        ->and($drawn->said())->toContain(__('household.tabs.profile'))
        ->and($drawn->offers())->toContain(__('household.switch_house'), __('household.app_settings'), __('household.remove_house'));
});

it('lists every house by name alone, the one the member is in marked, only while the list is open', function (): void {
    $first = aHouseOnTheirProfile('a', 'The loft');
    $second = aHouseOnTheirProfile('b', 'Zolder');
    $profile = new AMemberOnTheirProfile($first, $second)->profileOf($first);

    $closed = $profile->housesToChooseFrom();
    $profile->switchHouse();
    $open = $profile->housesToChooseFrom();
    $drawn = WhatTheDeviceWouldDraw::inTheListOfStacks($profile);

    expect($closed)->toBe([])
        ->and(array_map(static fn(AHouseToChooseAsShown $house): array => [$house->name, $house->current, $house->icon(), $house->iosIcon()], $open))
        ->toBe([['The loft', true, 'check_circle', 'checkmark.circle.fill'], ['Zolder', false, '', '']])
        ->and($drawn->offers())->toBe(['The loft', 'Zolder', __('household.add_house')])
        ->and(array_values(array_diff($drawn->said(), ['chevron_right'])))->toBe([__('household.your_houses'), 'The loft', 'Zolder', __('household.add_house')])
        ->and((string) json_encode(WhatTheDeviceWouldDraw::tree($profile)))->toContain(__('household.current_house', ['house' => 'The loft']), __('household.open_house', ['house' => 'Zolder']));
});

it('closes the list on the house the member is in, and opens another where choosing it leads', function (): void {
    $first = aHouseOnTheirProfile('a', 'The loft');
    $second = aHouseOnTheirProfile('b', 'Zolder');
    $profile = new AMemberOnTheirProfile($first, $second)->profileOf($first);
    $profile->switchHouse();
    [$here, $there] = $profile->housesToChooseFrom();

    expect($here->pressed())->toBe('stayInThisHouse()')
        ->and($there->pressed())->toBe(sprintf("openTheHouse('%s')", $second->id()->stored()));

    $profile->stayInThisHouse();
    expect($profile->choosingAHouse)->toBeFalse()
        ->and(whereProfileWent($profile))->toBe('nowhere');

    $profile->switchHouse();
    $profile->openTheHouse($second->id()->stored());
    expect($profile->choosingAHouse)->toBeFalse()
        ->and(whereProfileWent($profile))->toBe(sprintf('%s %s', NavigationIntent::NAVIGATE, AStacksScreen::Shelf->forTheStack($second->id())));
});

it('begins adding a house at pairing', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');

    expect(new AMemberOnTheirProfile($home)->profileOf($home)->addAHouse())->toBe(AScreenWithoutAStack::PairByScanning->value);
});

it('opens App settings in household words', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');
    $profile = new AMemberOnTheirProfile($home)->profileOf($home);

    expect($profile->appSettings())->toBe(AScreenWithoutAStack::Settings->value)
        ->and($profile->appSettingsSpeakTo())->toBe([AScreenWithoutAStack::SETTINGS_SPEAK_TO => WhoTheSettingsSpeakTo::AMember->value])
        ->and(WhatMarkupDraws::roads(sprintf(
            '<x-design::row headline="App settings" goes="%s" :carries="[\'%s\' => \'%s\']" />',
            $profile->appSettings(),
            AScreenWithoutAStack::SETTINGS_SPEAK_TO,
            WhoTheSettingsSpeakTo::AMember->value,
        )))->toHaveCount(1);
});

it('asks on the tab, naming the house, and keeps it when told to', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');
    $phone = new AMemberOnTheirProfile($home);
    $profile = $phone->profileOf($home);

    $profile->askToRemove();
    $asking = WhatTheDeviceWouldDraw::by($profile);
    $profile->keepTheHouse();

    expect($asking->said())->toContain(__('household.remove_house_confirm', ['house' => 'The loft']))
        ->and($asking->offers())->toContain(__('household.remove'), __('household.keep_it'))
        ->and($profile->confirmingTheRemoval)->toBeFalse()
        ->and($phone->stacks->configured()->knows($home->id()))->toBeTrue()
        ->and(whereProfileWent($profile))->toBe('nowhere');
});

it('takes the house off the phone, and starts over where choosing the next house leads', function (): void {
    $first = aHouseOnTheirProfile('a', 'The loft');
    $second = aHouseOnTheirProfile('b', 'Zolder');
    $phone = new AMemberOnTheirProfile($first, $second);
    $profile = $phone->profileOf($first);

    $profile->askToRemove();
    $profile->removeTheHouse();

    expect(whereProfileWent($profile))->toBe(sprintf('%s %s', NavigationIntent::RESET, AStacksScreen::Shelf->forTheStack($second->id())))
        ->and($phone->stacks->configured()->knows($first->id()))->toBeFalse()
        ->and($phone->keychain->keepsAnythingOf($first->id()))->toBeFalse()
        ->and($phone->stacks->configured()->knows($second->id()))->toBeTrue();
});

it('starts over on a first run where the house taken off was the only one', function (): void {
    $only = aHouseOnTheirProfile('a', 'The loft');
    $phone = new AMemberOnTheirProfile($only);
    $profile = $phone->profileOf($only);

    $profile->removeTheHouse();

    expect(whereProfileWent($profile))->toBe(sprintf('%s %s', NavigationIntent::RESET, AScreenWithoutAStack::TheList->value))
        ->and($phone->stacks->holdsAny())->toBeFalse();
});

it('says nothing was removed where the removal could not begin, and removes nothing', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');
    $phone = new AMemberOnTheirProfile($home);
    $profile = $phone->profileOf($home, journalWrites: false);

    $profile->askToRemove();
    $profile->removeTheHouse();
    $drawn = WhatTheDeviceWouldDraw::by($profile);

    expect($drawn->said())->toContain(__('household.remove_house_refused', ['house' => 'The loft']))
        ->and($profile->confirmingTheRemoval)->toBeFalse()
        ->and(whereProfileWent($profile))->toBe('nowhere')
        ->and($phone->stacks->configured()->knows($home->id()))->toBeTrue();
});

it('draws once more after its house is gone, as it does while it hands over', function (): void {
    $home = aHouseOnTheirProfile('a', 'The loft');
    $profile = new AMemberOnTheirProfile($home)->profileOf($home);
    $profile->stack();

    $profile->removeTheHouse();

    expect((string) json_encode(WhatTheDeviceWouldDraw::tree($profile)))->toContain(__('household.tabs.profile'));
});
