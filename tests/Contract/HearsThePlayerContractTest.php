<?php

declare(strict_types=1);

use Modules\Household\Internal\Playing\KeepsTheMembersPlace;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\WherePlayingStands;
use Tests\Support\AroundThePhone;
use Tests\Support\ATitleThatPlays;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsThePlace;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheTitlesOnScreen;

// The HearsThePlayer contract, run against every screen that shows Play.
//
// What each promises is the line beside Play after the player moved: why the
// title last played from it stopped and cannot go on, and nothing where
// nothing played from it stopped.

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

    $page = new WhatThisTitleIs($watching, $keychain, $titles, $around, new AppsSettingsThatOpen());
    $page->setParams(['stack' => $stack->id()->stored(), 'service' => 'a1']);

    $home = new WhatYouCanWatch($watching, AMemberWhoIsOwed::owedNothing(), $keychain, $titles, $around, new AppsSettingsThatOpen());
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
