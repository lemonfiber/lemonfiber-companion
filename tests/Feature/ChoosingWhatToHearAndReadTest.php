<?php

declare(strict_types=1);

use Modules\Household\Internal\ViewModels\ALanguageAsShown;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Whose;
use Tests\Support\AMemberOnTheirProfile;
use Tests\Support\WhatTheDeviceWouldDraw;

// A member chooses, from Profile, the language they hear titles in and the
// one they read subtitles in. The choice is theirs, on this phone, for this
// house: it is never sent to the house, and nobody else signed in here gets it.

/** A house a member chooses languages in. Named for this file. */
function aHouseToChooseLanguagesIn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.48'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * The words chosen in a row of chips, marked where chosen. Named for this file.
 *
 * @param list<ALanguageAsShown> $offered
 *
 * @return list<string>
 */
function theLanguagesOffered(array $offered): array
{
    return array_map(static fn(ALanguageAsShown $language): string => sprintf('%s%s', $language->word, $language->chosen ? '*' : ''), $offered);
}

it('offers every language to hear and to read, with the title as it comes chosen until the member chooses', function (): void {
    $house = aHouseToChooseLanguagesIn();
    $profile = new AMemberOnTheirProfile($house)->profileOf($house);
    $drawn = WhatTheDeviceWouldDraw::by($profile)->said();

    expect(theLanguagesOffered($profile->languages()->hear))->toBe(['original*', 'nl', 'en'])
        ->and(theLanguagesOffered($profile->languages()->read))->toBe(['none*', 'nl', 'en'])
        ->and($drawn)->toContain(__('household.languages.hear'), __('household.languages.read'), __('household.languages.kept_on_this_phone'));
});

it('keeps what the member chose for them on this house, and draws it chosen the next time Profile opens', function (): void {
    $house = aHouseToChooseLanguagesIn();
    $phone = new AMemberOnTheirProfile($house);
    $profile = $phone->profileOf($house);

    $profile->languages();
    $profile->hearIn(HearIn::Dutch->value);
    $profile->readIn(ReadIn::English->value);
    $profile->hearIn('not a language');
    $profile->readIn('not a language');
    $again = $phone->profileOf($house);

    expect(theLanguagesOffered($profile->languages()->hear))->toBe(['original', 'nl*', 'en'])
        ->and(theLanguagesOffered($again->languages()->read))->toBe(['none', 'nl', 'en*'])
        ->and($phone->languages->of($house->id(), Whose::member('robin')))->toEqual(TheirLanguages::of(HearIn::Dutch, ReadIn::English));
});

it('hands another member signed in to the same house nothing the first one chose', function (): void {
    $house = aHouseToChooseLanguagesIn();
    $phone = new AMemberOnTheirProfile($house);
    $phone->profileOf($house)->hearIn(HearIn::English->value);

    $phone->keychain->keep($house->id(), Session::of('another-session'), Whose::member('sam'));

    expect(theLanguagesOffered($phone->profileOf($house)->languages()->hear))->toBe(['original*', 'nl', 'en']);
});

it('keeps nothing, and offers the title as it comes, where nobody is signed in to the house', function (): void {
    $house = aHouseToChooseLanguagesIn();
    $phone = new AMemberOnTheirProfile($house);
    $phone->keychain->forgetTheStack($house->id());
    $profile = $phone->profileOf($house);

    $profile->hearIn(HearIn::Dutch->value);

    expect(theLanguagesOffered($profile->languages()->hear))->toBe(['original*', 'nl', 'en'])
        ->and($phone->languages->keepsAnythingOf($house->id()))->toBeFalse();
});
