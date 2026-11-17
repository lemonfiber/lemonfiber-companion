<?php

declare(strict_types=1);

use Modules\Household\Internal\Playing\KeepsTheChannelOpen;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Household\Internal\Screens\WhatIsOnLiveTv;
use Modules\Household\Internal\TheLiveTvGuide;
use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Household\Internal\ViewModels\WhatAGuideRowSays;
use Modules\Household\Internal\ViewModels\WhatAMarkSays;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlayingDidNotStart;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ATunerThatAnswered;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheChannelsOnScreen;
use Tests\Support\WhatTheDeviceWouldDraw;

// The live TV guide, opened from See all on Home's Live TV shelf: every
// channel's programmes across the window the core answers, a channel or the
// programme on now tuned, and a later programme's sheet.

/** Ten past eight in the evening, UTC, when these guides are read. */
const GUIDE_READ_AT = 1_893_442_200;

/** The stack the guide is read from. */
function theStackWithAGuide(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A keychain signed in to that stack as a member. */
function aMemberReadingTheGuide(): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theStackWithAGuide()->id(), Session::of('a-session-not-a-secret'), Whose::member('ana'));

    return $keychain;
}

/** NPO 1 until ten: the news on now, a gap where the core left one out, and a quiz at nine; and a radio channel whose guide holds nothing. */
function anEveningsGuide(): TheGuide
{
    return TheGuide::of(
        AChannelsGuide::of(
            ChannelId::called('c1'),
            'NPO 1',
            '1',
            WhenItSays::at(Instant::atEpochSeconds(1_893_448_800)),
            AnAiring::of('The news', Instant::atEpochSeconds(1_893_440_700), Instant::atEpochSeconds(1_893_443_400), '6'),
            AnAiring::of('The quiz', Instant::atEpochSeconds(1_893_445_200), Instant::atEpochSeconds(1_893_448_800), ''),
        ),
        AChannelsGuide::of(ChannelId::called('c2'), 'Radio', '', WhenItSays::unsaid()),
    );
}

/** The guide screen over a tuner, read on a phone set to that zone. */
function theGuideScreen(ATunerThatAnswered $tuner, AKeychainInMemory $keychain, ?APlayerOnAHandset $player = null, string $zone = 'UTC', ?TheChannelOnScreen $channel = null): WhatIsOnLiveTv
{
    $screen = new WhatIsOnLiveTv(
        new TheLiveTvGuide($keychain, $tuner, AZoneThatIsSet::to($zone), FrozenClock::at(Instant::atEpochSeconds(GUIDE_READ_AT))),
        TheChannelsOnScreen::over($tuner, $keychain, $player ?? APlayerOnAHandset::working(), $channel ?? new TheChannelOnScreen()),
        AroundThePhone::holding(StacksInMemory::holding(theStackWithAGuide()), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => theStackWithAGuide()->id()->stored()]);

    return $screen;
}

function aTunerWithAGuide(): ATunerThatAnswered
{
    return ATunerThatAnswered::listing(TheChannels::these())->guiding(anEveningsGuide());
}

/** The live stream the core opens for a channel. */
function aStreamOpenedFromTheGuide(): ALiveStream
{
    return ALiveStream::opened(
        's1',
        'https://192.168.1.20:8920/live/s1/stream.ts?lfpass=p',
        'https://192.168.1.20:8920/live/s1/main.m3u8?lfpass=p',
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
        Instant::atEpochSeconds(GUIDE_READ_AT + 300),
    );
}

it('draws every channel with its programmes along the half hours from the one the clock is in', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide()))->said();

    expect($said)->toContain('20:00')
        ->and($said)->toContain('21:30')
        ->and($said)->not->toContain('22:00')
        ->and($said)->toContain('Watch 1 · NPO 1')
        ->and($said)->toContain('Watch Radio')
        ->and($said)->toContain('NPO 1, The news, 19:45–20:30, on now')
        ->and($said)->toContain('The quiz, 21:00–22:00');
});

it('draws each programme as wide as it runs from the grid\'s start, and the time the core left out as empty space', function (): void {
    $guide = theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide())->guide();
    $widths = static fn(WhatAGuideRowSays $row): array => array_map(static fn(WhatACellSays $cell): array => [$cell->title, $cell->width, $cell->isAGap()], $row->cells);

    expect(array_map(static fn(WhatAMarkSays $mark): array => [$mark->time, $mark->width], $guide->marks))->toBe([['20:00', 120], ['20:30', 120], ['21:00', 120], ['21:30', 120]])
        ->and($widths($guide->rows[0]))->toBe([['The news', 120, false], ['', 120, true], ['The quiz', 240, false]])
        ->and($guide->rows[1]->cells)->toBe([]);
});

it('starts the grid at the half hour on the phone\'s own clock, whatever its zone', function (): void {
    $guide = theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide(), zone: 'Asia/Kathmandu')->guide();

    expect($guide->marks[0]->time)->toBe('01:30')
        ->and($guide->rows[0]->cells[0]->width)->toBe(180);
});

it('has pressed no channel when it opens, and reads as answered rather than signed out', function (): void {
    $screen = theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide());

    expect($screen->tunedTo)->toBe('')
        ->and($screen->guide()->cameBack)->toBeTrue()
        ->and($screen->guide()->isSignedOut())->toBeFalse();
});

it('asks for the guide once a frame', function (): void {
    $tuner = aTunerWithAGuide();
    $screen = theGuideScreen($tuner, aMemberReadingTheGuide());

    WhatTheDeviceWouldDraw::by($screen);
    $screen->guide();

    expect($tuner->guideAskings())->toBe(1);
});

it('reads the guide again when asked to', function (): void {
    $tuner = aTunerWithAGuide();
    $screen = theGuideScreen($tuner, aMemberReadingTheGuide());

    $screen->guide();
    $screen->askAgain();
    $screen->guide();

    expect($tuner->guideAskings())->toBe(2);
});

it('tunes a channel pressed by its name, or by the programme on it now, as the shelf does', function (): void {
    $player = APlayerOnAHandset::working();
    $tuner = aTunerWithAGuide()->tuning(WhatTuningCameTo::tuned(aStreamOpenedFromTheGuide()));
    $screen = theGuideScreen($tuner, aMemberReadingTheGuide(), $player);

    expect($screen->guide()->rows[0]->tap)->toBe('tuneTo(\'c1\')')
        ->and($screen->guide()->rows[0]->cells[0]->tap)->toBe('tuneTo(\'c1\')');

    $screen->tuneTo('');
    $screen->tuneTo('c1');

    expect($tuner->tuned())->toBe(['c1'])
        ->and($player->tuned())->toBe(['NPO 1'])
        ->and($screen->tunedTo)->toBe('c1')
        ->and($screen->guide()->came)->toBeNull();
});

it('says above the grid what came of a channel that would not play', function (): void {
    $tuner = aTunerWithAGuide()->tuning(WhatTuningCameTo::tuned(aStreamOpenedFromTheGuide()));
    $screen = theGuideScreen($tuner, aMemberReadingTheGuide(), APlayerOnAHandset::refusing(WhyPlayingDidNotStart::ThereIsNoPlayerHere));
    $screen->tuneTo('c1');

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('NPO 1: Playing isn\'t possible on this device.');
});

it('says above the grid how a channel tuned here stopped at its lapse, and nothing for one that closed', function (): void {
    $keychain = aMemberReadingTheGuide();
    $player = APlayerOnAHandset::working();
    $channel = new TheChannelOnScreen();
    $tuner = aTunerWithAGuide()->tuning(WhatTuningCameTo::tuned(aStreamOpenedFromTheGuide()));
    $screen = theGuideScreen($tuner, $keychain, $player, channel: $channel);

    $screen->playerMoved();
    expect($screen->guide()->came)->toBeNull();

    $screen->tuneTo('c1');
    $player->standing(WherePlayingStands::of(PlaybackIs::Playing, HowFarIn::theStart()));
    new KeepsTheChannelOpen($player, $channel, $tuner, $keychain, FrozenClock::at(Instant::atEpochSeconds(GUIDE_READ_AT + 300)))->heard();
    $screen->playerMoved();

    expect($screen->guide()->came?->stopped?->met)->toBe(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold())
        ->and($screen->guide()->came?->channel)->toBe('NPO 1');

    $closed = theGuideScreen($tuner, $keychain, $player, channel: $channel);
    $closed->tuneTo('c1');
    $player->standing(WherePlayingStands::of(PlaybackIs::Ended, HowFarIn::theStart()));
    $closed->playerMoved();

    expect($closed->guide()->came)->toBeNull();
});

it('names a channel it does not hold by its id', function (): void {
    expect(theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide())->guide()->nameOf('c9'))->toBe('c9');
});

it('opens a later programme\'s sheet with its channel, title, times and certificate, and nothing to press', function (): void {
    $screen = theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide());

    expect($screen->guide()->rows[0]->cells[2]->tap)->toBe('aboutTheProgramme(0, 1)');

    $screen->aboutTheProgramme(0, 1);
    $about = $screen->guide()->about;
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$about?->channel, $about?->title, $about?->times, $about?->certificate])->toBe(['NPO 1', 'The quiz', '21:00–22:00', ''])
        ->and($drawn->said())->toContain('21:00–22:00')
        ->and($drawn->offers())->not->toContain('The quiz');

    $screen->closeTheProgramme();

    expect($screen->guide()->about)->toBeNull();
});

it('opens no sheet for the programme on now, a gap, or a place nothing sits at', function (int $row, int $at): void {
    $screen = theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide());
    $screen->aboutTheProgramme($row, $at);

    expect($screen->guide()->about)->toBeNull();
})->with([
    'on now' => [0, 0],
    'a gap' => [0, -1],
    'past the programmes' => [0, 9],
    'a channel with none' => [1, 0],
    'past the channels' => [7, 0],
]);

it('says the house lists no live TV where the core answers no channel', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theGuideScreen(ATunerThatAnswered::listing(TheChannels::these()), aMemberReadingTheGuide()))->said();

    expect($said)->toContain(__('household.guide.none'));
});

it('says what stood in the way where the guide could not be had, and lets go of a refused session', function (): void {
    $keychain = aMemberReadingTheGuide();
    $screen = theGuideScreen(ATunerThatAnswered::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), $keychain);

    expect($screen->guide()->stopped?->met)->toBe(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold())
        ->and($screen->guide()->cameBack)->toBeFalse()
        ->and($screen->guide()->isSignedOut())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold()))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('household.ask_again'));

    theGuideScreen(ATunerThatAnswered::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain)->guide();

    expect($keychain->resume(theStackWithAGuide()->id())->either(held: static fn(): ArrayObject => new ArrayObject(['held']), notHeld: static fn(): ArrayObject => new ArrayObject(['let go']))->getArrayCopy())
        ->toBe(['let go']);
});

it('says the phone is signed out where nobody is signed in to read the guide as', function (): void {
    $screen = theGuideScreen(aTunerWithAGuide(), AKeychainInMemory::working());

    expect($screen->guide()->isSignedOut())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.signed_out'));
});

it('is what the router serves under the guide path, drawn under the Home tab', function (): void {
    expect(NativeRouter::resolve(AStacksScreen::Guide->forTheStack(theStackWithAGuide()->id()))['class'] ?? null)->toBe(WhatIsOnLiveTv::class)
        ->and(theGuideScreen(aTunerWithAGuide(), aMemberReadingTheGuide())->itsTab())->toBe(TheHouseholdsTabs::Home);
});
