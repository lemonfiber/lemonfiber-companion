<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Kernel\Api\AFormatInForce;
use Modules\Kernel\Api\APresetInForce;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneKindUpgraded;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WhatTheChoiceCameTo;
use Modules\Kernel\Api\WhereTheAskingStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\ChoosingHowGood;
use Modules\Operator\Internal\ViewModels\APresetAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatChoosesQuality;
use Tests\Support\Fakes\AStackThatUpgrades;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// How good the media should be: what is in force, choosing, confirming a held
// choice, and upgrading what is already here.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine this screen is about. */
function theStackWhoseQualityIsDrawn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The overall preset, which plays directly here. */
function theOverallPreset(): APresetInForce
{
    return APresetInForce::reported('everything', 'Balanced', 'Looks right on a TV', '1080p, good encodes', '~2 GB', 'Plays directly on most devices', transcodesHere: false);
}

/** A preset for films this machine would have to transcode. */
function aPresetThatTranscodesHere(): APresetInForce
{
    return APresetInForce::reported('movies', 'Maximum', '4K where it exists', '2160p, HDR kept', '~15 GB', 'Most clients cannot play it directly', transcodesHere: true);
}

/** The format chosen for music. */
function theFormatForMusic(): AFormatInForce
{
    return AFormatInForce::reported('music', 'Lossless', 'Nothing thrown away', 'FLAC', '~300 MB', 'Some players convert it');
}

/** The quality in force, become what is given. */
function theQualityThatBecame(WhatBecameOfTheChoice $became, bool $customised = false, ?WhatMusicIsSetTo $music = null): TheQualityChosen
{
    return TheQualityChosen::reported(
        ThePresetsInForce::of(theOverallPreset(), aPresetThatTranscodesHere()),
        $music ?? WhatMusicIsSetTo::set(theFormatForMusic()),
        $became,
        $customised,
    );
}

/** An upgrade covering films and television. */
function anUpgradeOfTwoKinds(): TheUpgrade
{
    return TheUpgrade::described(
        OneKindUpgraded::reported('movies', 'Maximum', '~15 GB', WhatBecameOfAskingIt::notAsked()),
        OneKindUpgraded::reported('tv', 'Space-saving', '~1 GB', WhatBecameOfAskingIt::notAsked()),
    );
}

/** The same upgrade, carried out, one service refusing. */
function anUpgradeCarriedOut(): TheUpgrade
{
    return TheUpgrade::carriedOut(
        OneKindUpgraded::reported('movies', 'Maximum', '~15 GB', WhatBecameOfAskingIt::asked(WhereTheAskingStands::Started)),
        OneKindUpgraded::reported('tv', 'Space-saving', '~1 GB', WhatBecameOfAskingIt::failed('Sonarr would not answer')),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theQualityScreen(
    AStackThatChoosesQuality $choosing,
    ?AStackThatUpgrades $upgrades = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): ChoosingHowGood {
    $stack = theStackWhoseQualityIsDrawn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new ChoosingHowGood(
        $choosing,
        $upgrades ?? AStackThatUpgrades::describing(anUpgradeOfTwoKinds(), anUpgradeCarriedOut()),
        $keychain,
        StacksInMemory::holding($stack),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The screen with a preset and a kind typed in. */
function typedIntoTheQualityScreen(ChoosingHowGood $screen, string $preset, string $kind = ''): ChoosingHowGood
{
    $screen->__syncProperty('preset', $preset);
    $screen->__syncProperty('kind', $kind);

    return $screen;
}

it('draws every preset in force in the stack\'s words, with what an hour of it costs', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown))))->said();

    expect($drawn)->toContain(__('quality.became.shown'))
        ->and($drawn)->toContain('Balanced')
        ->and($drawn)->toContain(__('quality.for', ['scope' => 'everything']))
        ->and($drawn)->toContain('Looks right on a TV')
        ->and($drawn)->toContain('1080p, good encodes')
        ->and($drawn)->toContain(__('quality.per_hour', ['size' => '~2 GB']))
        ->and($drawn)->toContain('Plays directly on most devices')
        ->and($drawn)->toContain('Maximum')
        ->and($drawn)->toContain(__('quality.for', ['scope' => 'movies']))
        ->and($drawn)->toContain(__('quality.per_hour', ['size' => '~15 GB']));
});

it('says a preset would transcode here on its own row, and only on that one', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown))))->said();

    // Last on the row it is true of: the name, what it is for, what it means,
    // its resolution, what an hour costs, what playing it costs, then this.
    expect(array_keys($drawn, __('quality.transcodes_here'), strict: true))
        ->toBe(array_map(static fn(int $at): int => $at + 6, array_keys($drawn, 'Maximum', strict: true)))
        ->toHaveCount(1);
});

it('draws music by its format and what that means, never as a resolution', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('quality.music.heading'))
        ->and($drawn)->toContain('Lossless')
        ->and($drawn)->toContain(__('quality.for', ['scope' => 'music']))
        ->and($drawn)->toContain('Nothing thrown away')
        ->and($drawn)->toContain(__('quality.music.targets', ['targets' => 'FLAC']))
        ->and($drawn)->toContain(__('quality.per_hour', ['size' => '~300 MB']))
        ->and($drawn)->toContain('Some players convert it')
        ->and(array_map(static fn(APresetAsShown $preset): string => $preset->preset, $screen->answer()->presets))->toBe(['Balanced', 'Maximum']);
});

it('says no format is chosen for music where none is', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown, music: WhatMusicIsSetTo::unset())));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('quality.music.unset'))
        ->and(get_object_vars($screen->answer()->music))->toBe(['scope' => '', 'format' => '', 'means' => '', 'targets' => '', 'sizePerHour' => '', 'note' => '']);
});

it('says a stack with no preset in force reports none', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(TheQualityChosen::reported(ThePresetsInForce::of(), WhatMusicIsSetTo::unset(), WhatBecameOfTheChoice::Shown, customised: false)));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('quality.no_presets'));
});

it('shows a hand-edited configuration as respected, and offers nothing that would put the preset back', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown, customised: true)));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('quality.customised'))
        ->and($drawn)->toContain(__('quality.not_put_back'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toBe([
            __('quality.choose.act'),
            __('quality.upgrade.describe'),
            __('health.ask_again'),
        ]);
});

it('says nothing of a hand-edit where there is none', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown))))->said();

    expect($drawn)->not->toContain(__('quality.customised'))
        ->and($drawn)->not->toContain(__('quality.not_put_back'));
});

it('chooses what was typed, for one kind of media, and draws what the stack recorded', function (): void {
    $choosing = AStackThatChoosesQuality::with(
        theQualityThatBecame(WhatBecameOfTheChoice::Shown),
        WhatTheChoiceCameTo::inForce(theQualityThatBecame(WhatBecameOfTheChoice::Recorded)),
    );
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), ' maximum ', 'movies');

    $screen->choose();

    expect([$choosing->chosen()?->preset(), $choosing->chosen()?->kind(), $choosing->confirmations()])->toBe(['maximum', 'movies', 0])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('quality.became.recorded'))
        ->and($screen->mayConfirm())->toBeFalse()
        ->and($choosing->readings())->toBe(0);
});

it('chooses for everything where no kind was typed', function (): void {
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Recorded));
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'balanced');

    $screen->choose();

    expect($choosing->chosen()?->isForEverything())->toBeTrue();
});

it('asks nothing where no preset was typed', function (): void {
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown));
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), '  ', 'movies');

    $screen->choose();

    expect($choosing->choices())->toBe(0);
});

it('shows a held choice with why it was held, and confirming is a tap of its own', function (): void {
    $choosing = AStackThatChoosesQuality::with(
        theQualityThatBecame(WhatBecameOfTheChoice::Shown),
        WhatTheChoiceCameTo::inForce(theQualityThatBecame(WhatBecameOfTheChoice::Held)),
    );
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'maximum', 'movies');

    $screen->choose();
    $held = WhatTheDeviceWouldDraw::by($screen);

    expect($held->said())->toContain(__('quality.became.held'))
        ->and($held->said())->toContain('Most clients cannot play it directly')
        ->and($screen->answer()->heldBecause)->toBe(['Most clients cannot play it directly'])
        ->and($held->offers())->toContain(__('quality.confirm'))
        ->and($held->said())->not->toContain(__('quality.became.recorded'))
        ->and($choosing->confirmations())->toBe(0);

    $screen->confirm();

    expect([$choosing->confirmations(), $choosing->confirmed()?->preset(), $choosing->confirmed()?->kind(), $choosing->choices()])
        ->toBe([1, 'maximum', 'movies', 1]);
});

it('offers no yes where nothing was held, and a yes tapped without one asks nothing', function (): void {
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Held));
    $screen = theQualityScreen($choosing);

    // Read rather than chosen: a held disposition on a reading is not a
    // choice this screen made, so there is nothing of the operator's to confirm.
    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('quality.confirm'))
        ->and($screen->answer()->heldBecause)->toBe(['Most clients cannot play it directly']);

    $screen->confirm();

    expect($choosing->confirmations())->toBe(0);
});

it('says a held choice came with no preset named as the reason, where none was', function (): void {
    $held = TheQualityChosen::reported(ThePresetsInForce::of(theOverallPreset()), WhatMusicIsSetTo::unset(), WhatBecameOfTheChoice::Held, customised: false);
    $screen = typedIntoTheQualityScreen(theQualityScreen(AStackThatChoosesQuality::with($held)), 'maximum');

    $screen->choose();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('quality.held_unexplained'))
        ->and($screen->mayConfirm())->toBeTrue();
});

it('says the stack names nothing to upgrade, where it names nothing', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), AStackThatUpgrades::describing(TheUpgrade::described(), TheUpgrade::carriedOut()));

    $screen->describe();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('quality.upgrade.nothing'));
});

it('lists why a choice was held only when it was held', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Recorded)));

    expect($screen->answer()->heldBecause)->toBe([]);
});

it('labels a rehearsed choice as a rehearsal and never as recorded', function (): void {
    $choosing = AStackThatChoosesQuality::with(
        theQualityThatBecame(WhatBecameOfTheChoice::Shown),
        WhatTheChoiceCameTo::inForce(theQualityThatBecame(WhatBecameOfTheChoice::Rehearsed)),
    );
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'maximum');

    $screen->choose();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('quality.became.rehearsed'))
        ->and($drawn)->not->toContain(__('quality.became.recorded'))
        ->and($screen->mayConfirm())->toBeFalse();
});

it('draws what choosing a format for music did, and reads what is in force again', function (): void {
    $made = AFormatChoiceMade::reported(theFormatForMusic(), WhatBecameOfTheChoice::Rehearsed, WhatBecameOfAskingIt::failed('Lidarr would not answer'));
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown), WhatTheChoiceCameTo::forMusic($made));
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'lossless', 'music');

    $screen->choose();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('quality.became.rehearsed'))
        ->and($drawn)->toContain(__('quality.asked.failed'))
        ->and($drawn)->toContain('Lidarr would not answer')
        ->and($screen->music?->format->format)->toBe('Lossless')
        ->and($screen->music?->detail)->toBe('Lidarr would not answer')
        ->and($choosing->readings())->toBe(1)
        ->and($screen->mayConfirm())->toBeFalse();
});

it('draws no line about asking the music service where it said nothing', function (): void {
    $made = AFormatChoiceMade::reported(theFormatForMusic(), WhatBecameOfTheChoice::Recorded, WhatBecameOfAskingIt::asked(WhereTheAskingStands::Started));
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown), WhatTheChoiceCameTo::forMusic($made));
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'lossless', 'music');

    $screen->choose();

    expect($screen->music?->detail)->toBe('')
        ->and($screen->music?->appliedSaid)->toBe('quality.asked.started')
        ->and($screen->music?->becameSaid)->toBe('quality.became.recorded');
});

it('describes upgrading kind by kind before anything is fetched, and upgrades only on a second tap', function (): void {
    $upgrades = AStackThatUpgrades::describing(anUpgradeOfTwoKinds(), anUpgradeCarriedOut());
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), $upgrades);

    $screen->describe();
    $described = WhatTheDeviceWouldDraw::by($screen);

    expect($described->said())->toContain(__('quality.upgrade.described'))
        ->and($described->said())->toContain(__('quality.upgrade.kind', ['kind' => 'movies', 'preset' => 'Maximum']))
        ->and($described->said())->toContain(__('quality.upgrade.kind', ['kind' => 'tv', 'preset' => 'Space-saving']))
        ->and($described->said())->toContain(__('quality.per_hour', ['size' => '~1 GB']))
        ->and($described->offers())->toContain(__('quality.upgrade.agree'))
        ->and($described->offers())->not->toContain(__('quality.upgrade.describe'))
        ->and([$upgrades->descriptions(), $upgrades->upgrades()])->toBe([1, 0]);

    $screen->upgrade();
    $done = WhatTheDeviceWouldDraw::by($screen);

    expect($done->said())->toContain(__('quality.upgrade.carried_out'))
        ->and($done->said())->toContain(__('quality.asked.started'))
        ->and($done->said())->toContain('Sonarr would not answer')
        ->and($done->offers())->not->toContain(__('quality.upgrade.agree'))
        ->and([$upgrades->descriptions(), $upgrades->upgrades()])->toBe([1, 1]);
});

it('upgrades nothing where nothing was described', function (): void {
    $upgrades = AStackThatUpgrades::describing(anUpgradeOfTwoKinds(), anUpgradeCarriedOut());
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), $upgrades);

    $screen->upgrade();

    expect($upgrades->upgrades())->toBe(0)
        ->and($screen->mayUpgrade())->toBeFalse()
        ->and($screen->upgrading)->toBeNull();
});

it('offers no second yes once an upgrade was carried out', function (): void {
    $upgrades = AStackThatUpgrades::describing(anUpgradeCarriedOut(), anUpgradeCarriedOut());
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), $upgrades);

    $screen->describe();

    expect($screen->mayUpgrade())->toBeFalse()
        ->and($screen->upgrading?->headingSaid)->toBe('quality.upgrade.carried_out');
});

it('says what stood in the way of an upgrade, and what to do, inside the screen', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), AStackThatUpgrades::met(Obstacle::StackDidNotAnswer));

    $screen->describe();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__(Obstacle::StackDidNotAnswer->said()))
        ->and($drawn)->toContain(__(Obstacle::StackDidNotAnswer->remedy()))
        ->and($drawn)->toContain('Balanced')
        ->and([$screen->upgrading?->kinds, $screen->upgrading?->headingSaid, $screen->upgrading?->went->cameBack()])->toBe([[], '', false])
        ->and($screen->mayUpgrade())->toBeFalse();
});

it('reads an upgrade whose credential was refused as a session that ended, and lets it go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), AStackThatUpgrades::met(Obstacle::CredentialWasRefused), $keychain);

    $screen->describe();

    expect([$screen->upgrading?->went->isSignedIn, $screen->upgrading?->headingSaid])->toBe([false, ''])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($keychain->isHolding(theStackWhoseQualityIsDrawn()->id()))->toBeFalse();
});

it('asks nothing about an upgrade once the session has ended', function (): void {
    $upgrades = AStackThatUpgrades::describing(anUpgradeOfTwoKinds(), anUpgradeCarriedOut());
    $screen = theQualityScreen(AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown)), $upgrades, signedIn: false);

    $screen->describe();

    expect($upgrades->descriptions())->toBe(0)
        ->and([$screen->upgrading?->went->isSignedIn, $screen->upgrading?->headingSaid, $screen->upgrading?->kinds])->toBe([false, '', []]);
});

it('says what stood in the way of a choice rather than that it was not made', function (): void {
    $choosing = AStackThatChoosesQuality::met(Obstacle::StackDidNotAnswer);
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'maximum');

    $screen->choose();

    expect($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($screen->answer()->becameSaid)->toBe('')
        ->and($screen->mayConfirm())->toBeFalse();
});

it('lets the session go when a choice is refused on the credential', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = typedIntoTheQualityScreen(theQualityScreen(AStackThatChoosesQuality::met(Obstacle::CredentialWasRefused), keychain: $keychain), 'maximum');

    $screen->choose();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseQualityIsDrawn()->id()))->toBeFalse();
});

it('asks nothing when choosing once the session has ended', function (): void {
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown));
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing, signedIn: false), 'maximum');

    $screen->choose();

    expect($choosing->choices())->toBe(0)
        ->and($screen->answer()->went->isSignedIn)->toBeFalse();
});

it('a stack that could not be asked is not a stack with nothing chosen', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::met(Obstacle::StackDidNotAnswer));
    $answer = $screen->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and([$answer->presets, $answer->music->format, $answer->becameSaid, $answer->heldBecause, $answer->customised])
        ->toBe([[], '', '', [], false])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('quality.no_presets'));
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theQualityScreen(AStackThatChoosesQuality::met(Obstacle::CredentialWasRefused), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseQualityIsDrawn()->id()))->toBeFalse();
});

it('a session that has ended asks the machine nothing', function (): void {
    $choosing = AStackThatChoosesQuality::with(theQualityThatBecame(WhatBecameOfTheChoice::Shown));

    expect(theQualityScreen($choosing, signedIn: false)->answer()->went->isSignedIn)->toBeFalse()
        ->and($choosing->readings())->toBe(0);
});

it('asks the machine once a frame, and again when asked to, forgetting every yes on offer', function (): void {
    $choosing = AStackThatChoosesQuality::with(
        theQualityThatBecame(WhatBecameOfTheChoice::Shown),
        WhatTheChoiceCameTo::inForce(theQualityThatBecame(WhatBecameOfTheChoice::Held)),
    );
    $screen = typedIntoTheQualityScreen(theQualityScreen($choosing), 'maximum');

    $screen->answer();
    $screen->answer();
    $screen->choose();
    $screen->describe();

    expect([$choosing->readings(), $screen->mayConfirm(), $screen->mayUpgrade()])->toBe([1, true, true]);

    $screen->again();

    expect([$screen->mayConfirm(), $screen->mayUpgrade(), $screen->music, $screen->upgrading])->toBe([false, false, null, null]);

    $screen->answer();

    expect($choosing->readings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theQualityScreen(AStackThatChoosesQuality::met(Obstacle::DeviceHasNoNetwork));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->ofItself()->quality()))->not->toBeNull();
});

it('renders its own view, with what was typed', function (): void {
    $view = typedIntoTheQualityScreen(theQualityScreen(AStackThatChoosesQuality::met(Obstacle::DeviceHasNoNetwork)), 'maximum', 'movies')->render();

    expect($view->name())->toBe('operator::choosing-how-good')
        ->and([$view->getData()['preset'] ?? null, $view->getData()['kind'] ?? null])->toBe(['maximum', 'movies']);
});
