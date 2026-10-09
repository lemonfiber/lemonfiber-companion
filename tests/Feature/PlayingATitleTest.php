<?php

declare(strict_types=1);

use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ASeason;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\Episodes;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\ItsDetails;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\WhyPlayingDidNotStart;
use Tests\Support\AroundThePhone;
use Tests\Support\ATitleThatPlays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\AStackThatGrants;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheTitlesOnScreen;
use Tests\Support\WhatTheDeviceWouldDraw;

// Pressing Play, on a title's page and across Home.
//
// The title is asked of the core again as Play is pressed, and the player is
// handed only what the core states for it then: its location, its door and
// the grant the core answered for this member on this device.

/** The title's page for `a1`, over these. */
function aPageToPressPlayOn(AShelfThatWasRead $watching, APlayerOnAHandset $player, ?AKeychainInMemory $keychain = null, ?Granting $core = null): WhatThisTitleIs
{
    $stack = ATitleThatPlays::theStack();
    $keychain ??= ATitleThatPlays::signedIn();
    $page = new WhatThisTitleIs(
        $watching,
        $keychain,
        TheTitlesOnScreen::over($watching, $keychain, $player, new WhatIsPlaying(), $core),
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $page->setParams(['stack' => $stack->id()->stored(), 'service' => 'a1']);

    return $page;
}

/** A series answered as `a1`: its first episode streams, its second does not. */
function aSeriesToPressPlayOn(): ATitle
{
    return ATitle::of(Holding::of(HoldingId::called('a1'), 'Slow Horses', Medium::Series, WhenItCameOut::in(2022)), ItsDetails::of('', HowLongItRuns::unstated(), Genres::of(), '', WhenItWasReleased::unstated()), WhereItPlays::doesNotStream(), Seasons::of(ASeason::of('Season 1', Episodes::of(
        AnEpisode::of(HoldingId::called('e1'), 'Failure\'s Contagious', NumberedAs::number(1), HowLongItRuns::minutes(49), '', ATitleThatPlays::streamingAtTheDoor('e1')),
        AnEpisode::of(HoldingId::called('e2'), 'Work Drinks', NumberedAs::number(2), HowLongItRuns::minutes(47), '', ATitleThatPlays::streamingAtTheDoor('e2')),
    ))));
}

/**
 * What the player was handed, each as one line: where, the door, the grant, from where and under what name.
 *
 * @return list<string>
 */
function whatThePlayerWasGiven(APlayerOnAHandset $player): array
{
    return array_map(
        static fn(ATitleToPlay $title): string => sprintf(
            '%s | %s | %s | %d | %s',
            $title->location()->forThePlayer(),
            $title->door()->forThePlayer(),
            $title->grant()->forTheDoor(),
            $title->startAt()->seconds(),
            $title->named(),
        ),
        $player->opened(),
    );
}

it('hands the player the location and door the core states, the grant it answered, from the start, under the title\'s name', function (): void {
    $watching = ATitleThatPlays::onTheShelf();
    $player = APlayerOnAHandset::working();

    aPageToPressPlayOn($watching, $player)->play();

    expect(whatThePlayerWasGiven($player))->toBe([sprintf(
        'https://192.168.1.42:8920/Videos/a1/master.m3u8 | %s | %s | 0 | Alien',
        str_repeat('d', 64),
        TheTitlesOnScreen::aGrant()->forTheDoor(),
    )])
        ->and($watching->titlesAskedFor())->toBe(['a1']);
});

it('plays a series\' first episode from its own Play, and the episode pressed from the episode\'s', function (): void {
    $watching = AShelfThatWasRead::holdingNothing()->answeringTheTitle(WhatTheTitleIs::told(aSeriesToPressPlayOn()));
    $player = APlayerOnAHandset::working();
    $page = aPageToPressPlayOn($watching, $player);

    $page->play();
    $page->playTheEpisode('e2');
    $page->playTheEpisode('not-an-episode-here');

    expect(array_map(static fn(string $line): string => (string) strrchr($line, '|'), whatThePlayerWasGiven($player)))
        ->toBe(['| Failure\'s Contagious', '| Work Drinks']);
});

it('says why where the device would not play what the core stated', function (WhyPlayingDidNotStart $why, string $said): void {
    $page = aPageToPressPlayOn(ATitleThatPlays::onTheShelf(), APlayerOnAHandset::refusing($why));

    $page->play();

    expect($page->playingSaid)->toBe($said)
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__($said));
})->with([
    'no player here' => [WhyPlayingDidNotStart::ThereIsNoPlayerHere, 'household.play.no_player'],
    'what the house said' => [WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed, 'household.play.cannot_be_played'],
]);

it('plays nothing and reads the page again where the core no longer plays it', function (): void {
    $watching = AShelfThatWasRead::holdingNothing()->answeringTheTitle(WhatTheTitleIs::absent());
    $player = APlayerOnAHandset::working();
    $page = aPageToPressPlayOn($watching, $player);

    $page->title();
    $page->play();
    $page->title();

    expect($player->opened())->toBe([])
        ->and($watching->titlesAskedFor())->toBe(['a1', 'a1', 'a1'])
        ->and($page->playingSaid)->toBe('');
});

it('lets go of a session the core refused when asked for a grant, and offers to sign in', function (): void {
    $keychain = ATitleThatPlays::signedIn();
    $player = APlayerOnAHandset::working();
    $page = aPageToPressPlayOn(ATitleThatPlays::onTheShelf(), $player, $keychain, AStackThatGrants::refusing(Obstacle::of(KindOfObstacle::CredentialWasRefused)));

    $page->play();

    expect($player->opened())->toBe([])
        ->and($keychain->isHolding(ATitleThatPlays::theStack()->id()))->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($page)->offers())->toBe([__('connection.sign_in')]);
});

it('plays nothing where no session is held', function (): void {
    $player = APlayerOnAHandset::working();

    aPageToPressPlayOn(ATitleThatPlays::onTheShelf(), $player, AKeychainInMemory::working())->play();

    expect($player->opened())->toBe([]);
});

/** Home over these. */
function aHomeToPressPlayOn(AShelfThatWasRead $watching, APlayerOnAHandset $player, ?Granting $core = null): WhatYouCanWatch
{
    $stack = ATitleThatPlays::theStack();
    $keychain = ATitleThatPlays::signedIn();
    $home = new WhatYouCanWatch(
        $watching,
        AMemberWhoIsOwed::owedNothing(),
        $keychain,
        TheTitlesOnScreen::over($watching, $keychain, $player, null, $core),
        AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain),
        new AppsSettingsThatOpen(),
    );
    $home->setParams(['stack' => $stack->id()->stored()]);

    return $home;
}

it('plays the title across Home from its Play', function (): void {
    $player = APlayerOnAHandset::working();
    $home = aHomeToPressPlayOn(ATitleThatPlays::onTheShelf(), $player);

    $home->play('a1');
    $home->play('');

    expect(array_map(static fn(string $line): string => (string) strrchr($line, '|'), whatThePlayerWasGiven($player)))->toBe(['| Alien']);
});

it('plays nothing across Home where the core no longer plays the title, and opens its page instead', function (): void {
    $player = APlayerOnAHandset::working();
    $home = aHomeToPressPlayOn(AShelfThatWasRead::holdingNothing()->answeringTheTitle(WhatTheTitleIs::absent()), $player);

    $home->play('a1');

    expect($player->opened())->toBe([])
        ->and($home->playingSaid)->toBe('');
});

it('says on Home what stood in the way of asking for the title', function (): void {
    $player = APlayerOnAHandset::working();
    $home = aHomeToPressPlayOn(ATitleThatPlays::onTheShelf(), $player, AStackThatGrants::refusing(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    $home->play('a1');

    expect($player->opened())->toBe([])
        ->and($home->answer()->cameBack())->toBeFalse();
});
