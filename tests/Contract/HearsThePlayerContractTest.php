<?php

declare(strict_types=1);

use Modules\Household\Internal\Playing\KeepsTheChannelOpen;
use Modules\Household\Internal\Playing\KeepsTheMembersPlace;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\Screens\WhatIsOnLiveTv;
use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Household\Internal\TheLiveTvGuide;
use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WherePlayingStands;
use Tests\Support\AroundThePhone;
use Tests\Support\ATitleThatPlays;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsThePlace;
use Tests\Support\Fakes\ATunerThatAnswered;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheChannelsOnScreen;
use Tests\Support\ThePicturesOnScreen;
use Tests\Support\TheTitlesOnScreen;

// The HearsThePlayer contract, run against every screen that shows Play or tunes a channel.
//
// What each promises is the line beside what was pressed after the player
// moved: why the title or channel last played from it stopped and cannot go
// on, and nothing where nothing played from it stopped.

/** When the channel's passes lapse in these tests. */
const A_CHANNELS_LAPSE = 1_893_456_300;

/**
 * Every screen that shows Play, over one player and one record of what plays, with how to press Play on it.
 *
 * @return array<string, array{WhatThisTitleIs|WhatYouCanWatch, Closure(): void}>
 */
function everyScreenThatHearsThePlayer(APlayerOnAHandset $player, WhatIsPlaying $playing): array
{
    $stack = ATitleThatPlays::theStack();
    $keychain = ATitleThatPlays::signedIn();
    $watching = ATitleThatPlays::onTheShelf();
    $titles = TheTitlesOnScreen::over($watching, $keychain, $player, $playing);
    $around = AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain);

    $page = new WhatThisTitleIs($watching, ThePicturesOnScreen::none(), $keychain, $titles, $around, new AppsSettingsThatOpen());
    $page->setParams(['stack' => $stack->id()->stored(), 'about' => 'a1']);

    $home = new WhatYouCanWatch($watching, ThePicturesOnScreen::none(), AMemberWhoIsOwed::owedNothing(), $keychain, $titles, TheChannelsOnScreen::none($keychain), $around, new AppsSettingsThatOpen());
    $home->setParams(['stack' => $stack->id()->stored()]);

    return [
        'the title\'s page' => [$page, static fn() => $page->play()],
        'Home' => [$home, static fn() => $home->play('a1')],
    ];
}

it('says nothing where nothing played from it', function (): void {
    foreach (everyScreenThatHearsThePlayer(APlayerOnAHandset::working(), new WhatIsPlaying()) as $which => [$screen]) {
        $screen->playerMoved();

        expect($screen->playingSaid)->toBe('', $which);
    }
});

it('says why where the title played from it stopped and cannot go on', function (): void {
    foreach (array_keys(everyScreenThatHearsThePlayer(APlayerOnAHandset::working(), new WhatIsPlaying())) as $which) {
        $player = APlayerOnAHandset::working()->standing(WherePlayingStands::of(PlaybackIs::StoppedOutOfReach, HowFarIn::at(61)));
        $playing = new WhatIsPlaying();
        [$screen, $press] = everyScreenThatHearsThePlayer($player, $playing)[$which];

        $press();
        new KeepsTheMembersPlace($player, $playing, AStackThatKeepsThePlace::keeping(), ATitleThatPlays::signedIn(), TheTitlesOnScreen::over(ATitleThatPlays::onTheShelf(), ATitleThatPlays::signedIn(), $player, $playing))->heard();
        $screen->playerMoved();

        expect($screen->playingSaid)->toBe('household.play.out_of_reach', $which);
    }
});

/**
 * Every screen that tunes a channel, over one tuner, player and channel on screen, with how to press the channel and what it says beside it.
 *
 * @return array<string, array{WhatYouCanWatch|WhatIsOnLiveTv, Closure(): ?WhatAChannelCameTo}>
 */
function everyScreenThatHearsAChannel(ATunerThatAnswered $tuner, APlayerOnAHandset $player, TheChannelOnScreen $channel): array
{
    $stack = ATitleThatPlays::theStack();
    $keychain = ATitleThatPlays::signedIn();
    $watching = ATitleThatPlays::onTheShelf();
    $around = AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain);
    $channels = TheChannelsOnScreen::over($tuner, $keychain, $player, $channel);

    $home = new WhatYouCanWatch($watching, ThePicturesOnScreen::none(), AMemberWhoIsOwed::owedNothing(), $keychain, TheTitlesOnScreen::over($watching, $keychain, $player), $channels, $around, new AppsSettingsThatOpen());
    $home->setParams(['stack' => $stack->id()->stored()]);

    $guide = new WhatIsOnLiveTv(new TheLiveTvGuide($keychain, $tuner, AZoneThatIsSet::to('UTC'), FrozenClock::at(Instant::atEpochSeconds(A_CHANNELS_LAPSE - 600))), $channels, $around, new AppsSettingsThatOpen());
    $guide->setParams(['stack' => $stack->id()->stored()]);

    return [
        'Home' => [$home, static fn(): ?WhatAChannelCameTo => $home->channels()->came],
        'the guide' => [$guide, static fn(): ?WhatAChannelCameTo => $guide->guide()->came],
    ];
}

/** A tuner listing one channel, in its list and its guide, which opens it on a pass lapsing at {@see A_CHANNELS_LAPSE} and renews nothing. */
function aTunerOfOneChannel(): ATunerThatAnswered
{
    return ATunerThatAnswered::listing(TheChannels::these(AChannel::withNothingSaid(ChannelId::called('c1'), 'NPO 1', '1')))
        ->guiding(TheGuide::of(AChannelsGuide::of(ChannelId::called('c1'), 'NPO 1', '1', WhenItSays::unsaid())))
        ->tuning(WhatTuningCameTo::tuned(ALiveStream::opened('s1', 'https://192.168.1.20:8920/live/s1/stream.ts?lfpass=p', '', Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)), Instant::atEpochSeconds(A_CHANNELS_LAPSE))));
}

it('says nothing beside a channel where no channel played from it', function (): void {
    foreach (everyScreenThatHearsAChannel(aTunerOfOneChannel(), APlayerOnAHandset::working(), new TheChannelOnScreen()) as $which => [$screen, $beside]) {
        $screen->playerMoved();

        expect($beside())->toBeNull($which);
    }
});

it('says why beside a channel tuned from it that stopped at its lapse', function (): void {
    foreach (array_keys(everyScreenThatHearsAChannel(aTunerOfOneChannel(), APlayerOnAHandset::working(), new TheChannelOnScreen())) as $which) {
        $tuner = aTunerOfOneChannel();
        $player = APlayerOnAHandset::working();
        $channel = new TheChannelOnScreen();
        [$screen, $beside] = everyScreenThatHearsAChannel($tuner, $player, $channel)[$which];

        $screen->tuneTo('c1');
        $player->standing(WherePlayingStands::of(PlaybackIs::Playing, HowFarIn::theStart()));
        new KeepsTheChannelOpen($player, $channel, $tuner, ATitleThatPlays::signedIn(), FrozenClock::at(Instant::atEpochSeconds(A_CHANNELS_LAPSE)))->heard();
        $screen->playerMoved();

        expect($beside()?->stopped?->met)->toBe(Obstacle::of(KindOfObstacle::StackDidNotAnswer)->saidToTheHousehold(), $which)
            ->and($beside()?->channel)->toBe('NPO 1', $which);
    }
});
