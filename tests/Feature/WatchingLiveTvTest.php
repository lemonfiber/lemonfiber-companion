<?php

declare(strict_types=1);

use Modules\Household\Internal\Playing\KeepsTheChannelOpen;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\WhatRenewingCameTo;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlayingDidNotStart;
use Tests\Support\AroundThePhone;
use Tests\Support\ATitleThatPlays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\ATunerThatAnswered;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheChannelsOnScreen;
use Tests\Support\ThePicturesOnScreen;
use Tests\Support\TheTitlesOnScreen;
use Tests\Support\WhatTheDeviceWouldDraw;

// Live TV on Home: a shelf of channels with what is on now, a channel pressed
// and put on the player, its pass renewed before it lapses, and how it stopped
// said beside the shelf.

/** When the stream's passes lapse in these tests. */
const A_LAPSE = 1_893_456_300;

/** The stack live TV is watched on. */
function theStackWithLiveTv(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A keychain signed in to that stack as a member. */
function aMemberWatchingLiveTv(): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theStackWithLiveTv()->id(), Session::of('a-session-not-a-secret'), Whose::member('ana'));

    return $keychain;
}

/** Two channels: one with what is on until 20:30 UTC, one the guide says nothing of. */
function twoChannels(): TheChannels
{
    return TheChannels::these(
        AChannel::showing(ChannelId::called('c1'), 'NPO 1', '1', AProgramme::of('The news', WhenItSays::at(Instant::atEpochSeconds(1_893_443_400)), '6')),
        AChannel::withNothingSaid(ChannelId::called('c2'), 'Radio', ''),
    );
}

/** The live stream the core opens for a channel. */
function aStreamOpened(): ALiveStream
{
    return ALiveStream::opened(
        's1',
        'https://192.168.1.20:8920/live/s1/stream.ts?lfpass=p',
        'https://192.168.1.20:8920/live/s1/main.m3u8?lfpass=p',
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
        Instant::atEpochSeconds(A_LAPSE),
    );
}

/** Home over a tuner, with the player and what is on it shared with whatever keeps the channel open. */
function homeWithLiveTv(ATunerThatAnswered $tuner, AKeychainInMemory $keychain, APlayerOnAHandset $player, TheChannelOnScreen $channel): WhatYouCanWatch
{
    $watching = AShelfThatWasRead::holdingNothing();
    $home = new WhatYouCanWatch(
        $watching,
        ThePicturesOnScreen::none(),
        AMemberWhoIsOwed::owedNothing(),
        $keychain,
        TheTitlesOnScreen::over($watching, $keychain, $player),
        TheChannelsOnScreen::over($tuner, $keychain, $player, $channel),
        AroundThePhone::holding(StacksInMemory::holding(theStackWithLiveTv()), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $home->setParams(['stack' => theStackWithLiveTv()->id()->stored()]);

    return $home;
}

/** What keeps the channel open, at a moment so many seconds before the lapse. */
function keeperAt(int $beforeTheLapse, ATunerThatAnswered $tuner, AKeychainInMemory $keychain, APlayerOnAHandset $player, TheChannelOnScreen $channel): KeepsTheChannelOpen
{
    return new KeepsTheChannelOpen($player, $channel, $tuner, $keychain, FrozenClock::at(Instant::atEpochSeconds(A_LAPSE - $beforeTheLapse)));
}

/**
 * A channel on the player, tuned from Home, with everything that hears it.
 *
 * @return list{WhatYouCanWatch, AKeychainInMemory, APlayerOnAHandset, TheChannelOnScreen}
 */
function aChannelOnThePlayer(ATunerThatAnswered $tuner): array
{
    $keychain = aMemberWatchingLiveTv();
    $player = APlayerOnAHandset::working();
    $channel = new TheChannelOnScreen();
    $home = homeWithLiveTv($tuner->tuning(WhatTuningCameTo::tuned(aStreamOpened())), $keychain, $player, $channel);
    $home->tuneTo('c1');

    return [$home, $keychain, $player, $channel];
}

function playing(PlaybackIs $is): WherePlayingStands
{
    return WherePlayingStands::of($is, HowFarIn::theStart());
}

it('draws a Live TV shelf after Home\'s rows, each channel with what is on it now until when', function (): void {
    $home = homeWithLiveTv(ATunerThatAnswered::listing(twoChannels()), aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());
    $said = WhatTheDeviceWouldDraw::by($home)->said();

    expect($said)->toContain(__('household.live.heading'))
        ->and($said)->toContain('1 · NPO 1, The news, until 20:30')
        ->and($said)->toContain('Radio, Nothing in the guide right now');
});

it('offers See all under the shelf, which opens the guide', function (): void {
    $home = homeWithLiveTv(ATunerThatAnswered::listing(twoChannels()), aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());

    expect(WhatTheDeviceWouldDraw::by($home)->offers())->toContain(__('household.live.see_all_named'));
});

it('draws no shelf where the core lists no channel, or will not list them', function (ATunerThatAnswered $tuner): void {
    $home = homeWithLiveTv($tuner, aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());

    expect(WhatTheDeviceWouldDraw::by($home)->said())->not->toContain(__('household.live.heading'));
})->with([
    'none listed' => [ATunerThatAnswered::listing(TheChannels::these())],
    'not reached' => [ATunerThatAnswered::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
]);

it('asks for the channels once a frame', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    $home = homeWithLiveTv($tuner, aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());

    WhatTheDeviceWouldDraw::by($home);
    $home->channels();

    expect($tuner->askings())->toBe(1);
});

it('lets go of a session the stack refused while listing the channels', function (): void {
    $keychain = aMemberWatchingLiveTv();
    homeWithLiveTv(ATunerThatAnswered::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $keychain, APlayerOnAHandset::working(), new TheChannelOnScreen())->channels();

    expect($keychain->resume(theStackWithLiveTv()->id())->either(held: static fn(): ArrayObject => new ArrayObject(['held']), notHeld: static fn(): ArrayObject => new ArrayObject(['let go']))->getArrayCopy())
        ->toBe(['let go']);
});

it('puts a pressed channel on the player under its name, and says nothing beside it', function (): void {
    [$home, , $player, $channel] = aChannelOnThePlayer(ATunerThatAnswered::listing(twoChannels()));

    expect($player->tuned())->toBe(['NPO 1'])
        ->and($channel->now()?->channel()->named())->toBe('c1')
        ->and($home->channels()->came)->toBeNull();
});

it('tells the core the channel a new one replaces on the player stopped', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    [$home] = aChannelOnThePlayer($tuner);

    $home->tuneTo('c2');

    expect($tuner->tuned())->toBe(['c1', 'c2'])
        ->and($tuner->stopped())->toBe(['s1']);
});

it('says the house signed this phone out where nobody is signed in to tune as, and presses nothing for no channel', function (): void {
    $keychain = aMemberWatchingLiveTv();
    $tuner = ATunerThatAnswered::listing(twoChannels())->tuning(WhatTuningCameTo::tuned(aStreamOpened()));
    $home = homeWithLiveTv($tuner, $keychain, APlayerOnAHandset::working(), new TheChannelOnScreen());
    $home->channels();
    $home->tuneTo('');
    $keychain->forget(theStackWithLiveTv()->id());
    $home->tuneTo('c1');

    expect($tuner->tuned())->toBe([])
        ->and($home->channels()->came?->key)->toBe('household.signed_out')
        ->and($home->tunedTo)->toBe('c1');
});

it('says the core\'s own words beside the shelf where it locates nothing, as away from home', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->tuning(WhatTuningCameTo::cannot(Sentence::of('Live TV plays only at home.')));
    $home = homeWithLiveTv($tuner, aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());
    $home->tuneTo('c1');

    expect(WhatTheDeviceWouldDraw::by($home)->said())->toContain('NPO 1: Live TV plays only at home.');
});

it('tells the core a stream the player would not take stopped, and says why in household words', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->tuning(WhatTuningCameTo::tuned(aStreamOpened()));
    $home = homeWithLiveTv($tuner, aMemberWatchingLiveTv(), APlayerOnAHandset::refusing(WhyPlayingDidNotStart::ThereIsNoPlayerHere), new TheChannelOnScreen());
    $home->tuneTo('c1');

    expect($tuner->stopped())->toBe(['s1'])
        ->and(WhatTheDeviceWouldDraw::by($home)->said())->toContain('NPO 1: Playing isn\'t possible on this device.');
});

it('says what stood in the way where asking for the channel met an obstacle', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $tuner = ATunerThatAnswered::listing(twoChannels())->tuning(WhatTuningCameTo::refused($why));
    $home = homeWithLiveTv($tuner, aMemberWatchingLiveTv(), APlayerOnAHandset::working(), new TheChannelOnScreen());
    $home->tuneTo('c1');

    expect($home->channels()->came?->stopped?->met)->toBe($why->saidToTheHousehold());
});

it('renews the passes only once they are within reach of the lapse', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->renewing(WhatRenewingCameTo::renewedUntil(Instant::atEpochSeconds(A_LAPSE + 300)));
    [, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Paused));

    keeperAt(121, $tuner, $keychain, $player, $channel)->heard();
    expect($tuner->renewed())->toBe([]);

    keeperAt(120, $tuner, $keychain, $player, $channel)->heard();
    expect($tuner->renewed())->toBe(['s1'])
        ->and($channel->now()?->stream()->lapses()->epochSeconds())->toBe(A_LAPSE + 300);
});

it('renews nothing after the core refused in its own words, and stops at the lapse saying them', function (): void {
    $refused = Obstacle::of(KindOfObstacle::NotForThisAccount)->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said('Live TV is off for your account.', 'Ask whoever looks after the house.'));
    $tuner = ATunerThatAnswered::listing(twoChannels())->renewing(WhatRenewingCameTo::refused($refused));
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Playing));

    keeperAt(60, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(30, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(1, $tuner, $keychain, $player, $channel)->heard();
    expect($tuner->renewed())->toBe(['s1'])
        ->and($player->timesClosed())->toBe(0);

    keeperAt(0, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect($player->timesClosed())->toBe(1)
        ->and($tuner->stopped())->toBe(['s1'])
        ->and($channel->now())->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($home)->said())->toContain('Live TV is off for your account.');
});

it('asks a core it could not reach again on each report, and stops at the lapse in its own words', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->renewing(WhatRenewingCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Playing));

    keeperAt(60, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(50, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(-1, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect($tuner->renewed())->toBe(['s1', 's1'])
        ->and($player->timesClosed())->toBe(1)
        ->and($home->channels()->came?->stopped?->met)->toBe(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold());
});

it('stops at a lapse no report came near in time to renew, saying the house did not answer', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Playing));

    keeperAt(KeepsTheChannelOpen::RENEWS_WITHIN + 60, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(0, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect($tuner->renewed())->toBe([])
        ->and($player->timesClosed())->toBe(1)
        ->and($home->channels()->came?->stopped?->met)->toBe(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold());
});

it('treats a session no longer held as a refusal, and renews nothing more', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->renewing(WhatRenewingCameTo::renewedUntil(Instant::atEpochSeconds(A_LAPSE + 300)));
    [, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Playing));
    $keychain->forget(theStackWithLiveTv()->id());

    keeperAt(60, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(30, $tuner, $keychain, $player, $channel)->heard();

    expect($tuner->renewed())->toBe([])
        ->and($channel->now()?->wasRefused())->toBeTrue();
});

it('plays on where a renewal that could not reach the core reaches it on the next report', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels())->renewing(WhatRenewingCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    [, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::Playing));

    keeperAt(60, $tuner, $keychain, $player, $channel)->heard();
    $tuner->renewing(WhatRenewingCameTo::renewedUntil(Instant::atEpochSeconds(A_LAPSE + 300)));
    keeperAt(50, $tuner, $keychain, $player, $channel)->heard();
    keeperAt(0, $tuner, $keychain, $player, $channel)->heard();

    expect($player->timesClosed())->toBe(0)
        ->and($channel->now()?->stream()->lapses()->epochSeconds())->toBe(A_LAPSE + 300);
});

it('tells the core a channel closed on the player stopped, and says nothing', function (PlaybackIs $is): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing($is));

    keeperAt(0, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect($tuner->stopped())->toBe(['s1'])
        ->and($player->timesClosed())->toBe(0)
        ->and($channel->now())->toBeNull()
        ->and($home->channels()->came)->toBeNull();
})->with([PlaybackIs::Closed, PlaybackIs::Ended]);

it('says beside the shelf why a channel stopped on the player, and tells the core', function (PlaybackIs $is, string $said): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing($is));

    keeperAt(200, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect($tuner->stopped())->toBe(['s1'])
        ->and($player->timesClosed())->toBe(1)
        ->and($channel->now())->toBeNull()
        ->and($home->channels()->came?->key)->toBe($said);
})->with([
    'out of reach' => [PlaybackIs::StoppedOutOfReach, 'household.play.out_of_reach'],
    'by the pin' => [PlaybackIs::StoppedByThePin, 'household.play.not_the_house'],
    'on the format' => [PlaybackIs::StoppedOnTheFormat, 'household.play.not_on_this_device'],
    'at the door' => [PlaybackIs::StoppedAtTheDoor, 'household.play.not_let_in'],
]);

it('draws why a channel stopped on the player in the household\'s words', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    [$home, $keychain, $player, $channel] = aChannelOnThePlayer($tuner);
    $player->standing(playing(PlaybackIs::StoppedOutOfReach));

    keeperAt(200, $tuner, $keychain, $player, $channel)->heard();
    $home->playerMoved();

    expect(WhatTheDeviceWouldDraw::by($home)->said())->toContain('NPO 1: The house can\'t be reached from here. Playing works when you\'re at home.');
});

it('keeps nothing open once a title goes on the player', function (): void {
    $tuner = ATunerThatAnswered::listing(twoChannels());
    $keychain = aMemberWatchingLiveTv();
    $player = APlayerOnAHandset::working();
    $channel = new TheChannelOnScreen();
    homeWithLiveTv($tuner->tuning(WhatTuningCameTo::tuned(aStreamOpened())), $keychain, $player, $channel)->tuneTo('c1');

    TheTitlesOnScreen::over(ATitleThatPlays::onTheShelf(), ATitleThatPlays::signedIn(), $player, new WhatIsPlaying(), channel: $channel)->theTitle(ATitleThatPlays::theStack(), HoldingId::called('a1'));

    expect($channel->now())->toBeNull()
        ->and($player->opened())->toHaveCount(1);
});
