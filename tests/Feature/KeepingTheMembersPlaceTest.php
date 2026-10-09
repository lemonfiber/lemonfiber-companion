<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\WhenThePlayerMoves;
use Modules\Household\Internal\Playing\KeepsTheMembersPlace;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\Whose;
use Tests\Support\APlaybackHeard;
use Tests\Support\AroundThePhone;
use Tests\Support\ATitleThatPlays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatGrants;
use Tests\Support\Fakes\AStackThatKeepsThePlace;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheTitlesOnScreen;

// What the player moving means: the member's place told to the core as it is
// heard, and nothing queued; a title that stopped and cannot go on closed and
// said; a grant the door refused asked for again, once.

/** A second grant, for the door to be asked again with. */
function aGrantAskedForAgain(): AGrant
{
    return AGrant::of(str_repeat('0d', 16), Instant::atEpochSeconds(2_000_000_000));
}

/** `a1` put on the player by Play, over these. */
function aPlaybackUnderWay(?AStackThatKeepsThePlace $core = null, ?AKeychainInMemory $keychain = null): APlaybackHeard
{
    $keychain ??= ATitleThatPlays::signedIn();
    $core ??= AStackThatKeepsThePlace::keeping();
    $player = APlayerOnAHandset::working();
    $playing = new WhatIsPlaying();
    $titles = TheTitlesOnScreen::over(ATitleThatPlays::onTheShelf(), $keychain, $player, $playing, AStackThatGrants::granting(TheTitlesOnScreen::aGrant(), aGrantAskedForAgain()));
    $titles->theTitle(ATitleThatPlays::theStack(), HoldingId::called('a1'));

    return new APlaybackHeard(new KeepsTheMembersPlace($player, $playing, $core, $keychain, $titles), $playing, $player, $core, $keychain);
}

/** Where the player stands. */
function standing(PlaybackIs $is, int $seconds): WherePlayingStands
{
    return WherePlayingStands::of($is, HowFarIn::at($seconds));
}

it('tells the core where the member is while it plays and when it pauses', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::Playing, 30), standing(PlaybackIs::Paused, 45));

    $under->place->heard();
    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 30', 'a1 at 45'])
        ->and($under->playing->now())->not->toBeNull();
});

it('tells nothing while it opens or waits for the door', function (PlaybackIs $is): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing($is, 30));

    $under->place->heard();

    expect($under->core->told())->toBe([])
        ->and($under->playing->now())->not->toBeNull();
})->with([PlaybackIs::Opening, PlaybackIs::Stalled]);

it('tells the end once, and is over', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::Ended, 5_400), standing(PlaybackIs::Closed, 5_400));

    $under->place->heard();
    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 5400, the end'])
        ->and($under->playing->now())->toBeNull();
});

it('tells where it was closed, and is over', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::Closed, 1_200));

    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 1200'])
        ->and($under->playing->now())->toBeNull();
});

it('tells nothing of a player closed at the start, which is also how a device with no player answers', function (): void {
    $under = aPlaybackUnderWay();

    $under->place->heard();

    expect($under->core->told())->toBe([])
        ->and($under->playing->now())->toBeNull();
});

it('tells where it stopped, closes the player and keeps why for the page', function (PlaybackIs $is): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing($is, 61));

    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 61'])
        ->and($under->player->timesClosed())->toBe(1)
        ->and($under->playing->now())->toBeNull()
        ->and($under->playing->howItStoppedOn(HoldingId::called('a1')))->toBe($is)
        ->and($under->playing->howItStoppedOn(HoldingId::called('a2')))->toBe(PlaybackIs::Closed);
})->with([PlaybackIs::StoppedOutOfReach, PlaybackIs::StoppedByThePin, PlaybackIs::StoppedOnTheFormat]);

it('asks for a grant again where the door refused one, and puts the title back where it stopped', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::StoppedAtTheDoor, 61));

    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 61'])
        ->and(array_map(static fn(ATitleToPlay $title): string => sprintf('%s from %d', $title->grant()->forTheDoor(), $title->startAt()->seconds()), $under->player->opened()))
        ->toBe([sprintf('%s from 0', TheTitlesOnScreen::aGrant()->forTheDoor()), sprintf('%s from 61', aGrantAskedForAgain()->forTheDoor())])
        ->and($under->player->timesClosed())->toBe(0)
        ->and($under->playing->now()?->wasGrantedAgain())->toBeTrue();
});

it('stops where the door refused the grant asked for again too', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::StoppedAtTheDoor, 61), standing(PlaybackIs::StoppedAtTheDoor, 62));

    $under->place->heard();
    $under->place->heard();

    expect($under->player->opened())->toHaveCount(2)
        ->and($under->player->timesClosed())->toBe(1)
        ->and($under->playing->howItStoppedOn(HoldingId::called('a1')))->toBe(PlaybackIs::StoppedAtTheDoor);
});

it('stops where nobody is signed in to ask the grant again under', function (): void {
    $under = aPlaybackUnderWay();
    $under->keychain->forget(ATitleThatPlays::theStack()->id());
    $under->player->standing(standing(PlaybackIs::StoppedAtTheDoor, 61));

    $under->place->heard();

    expect($under->player->opened())->toHaveCount(1)
        ->and($under->playing->howItStoppedOn(HoldingId::called('a1')))->toBe(PlaybackIs::StoppedAtTheDoor);
});

it('tells nothing once somebody else signed in on that stack', function (): void {
    $under = aPlaybackUnderWay();
    $under->keychain->keep(ATitleThatPlays::theStack()->id(), Session::of('another-session-not-a-secret'), Whose::member('bob'));
    $under->player->standing(standing(PlaybackIs::Playing, 30));

    $under->place->heard();

    expect($under->core->told())->toBe([]);
});

it('lets go of a session the core refused while being told the place', function (): void {
    $under = aPlaybackUnderWay(AStackThatKeepsThePlace::refusing(Obstacle::of(KindOfObstacle::CredentialWasRefused)));
    $under->player->standing(standing(PlaybackIs::Playing, 30));

    $under->place->heard();

    expect($under->core->told())->toBe(['a1 at 30'])
        ->and($under->keychain->isHolding(ATitleThatPlays::theStack()->id()))->toBeFalse();
});

it('keeps the session where the core answered something else', function (): void {
    $under = aPlaybackUnderWay(AStackThatKeepsThePlace::refusing(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $under->player->standing(standing(PlaybackIs::Playing, 30));

    $under->place->heard();

    expect($under->keychain->isHolding(ATitleThatPlays::theStack()->id()))->toBeTrue();
});

it('tells the place and has the page say why, when the device says the player moved', function (): void {
    $stack = ATitleThatPlays::theStack();
    $keychain = ATitleThatPlays::signedIn();
    $watching = ATitleThatPlays::onTheShelf();
    $player = APlayerOnAHandset::working();
    $playing = new WhatIsPlaying();
    $core = AStackThatKeepsThePlace::keeping();
    $titles = TheTitlesOnScreen::over($watching, $keychain, $player, $playing);
    $page = new WhatThisTitleIs($watching, $keychain, $titles, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain), new AppsSettingsThatOpen());
    $page->setParams(['stack' => $stack->id()->stored(), 'service' => 'a1']);
    $moved = new WhenThePlayerMoves(new KeepsTheMembersPlace($player, $playing, $core, $keychain, $titles));

    $page->play();
    $player->standing(standing(PlaybackIs::Playing, 30), standing(PlaybackIs::StoppedOutOfReach, 40));
    $moved->over(null);
    $moved->over($page);

    expect($core->told())->toBe(['a1 at 30', 'a1 at 40'])
        ->and($page->playingSaid)->toBe('household.play.out_of_reach');
});

it('tells the place when heard from the dispatcher, whatever screen is on view', function (): void {
    $under = aPlaybackUnderWay();
    $under->player->standing(standing(PlaybackIs::Playing, 30));

    new WhenThePlayerMoves($under->place)->handle();

    expect($under->core->told())->toBe(['a1 at 30']);
});
