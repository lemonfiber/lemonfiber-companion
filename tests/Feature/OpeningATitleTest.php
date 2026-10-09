<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ASeason;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Episodes;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\ItsDetails;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatMarkupDraws;
use Tests\Support\WhatTheDeviceWouldDraw;

// One title's screen, opened from its poster or from Home's hero.
//
// It reads the title the route names, as the member, and draws the core's
// answer. Here rather than in the household module's own tests because a
// screen renders, and rendering needs the application.

/** The machine the title is on. */
function theStackATitleIsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('t', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** Where the core says a title streams from, at the door. */
function streamingAtTheDoor(string $id): WhereItPlays
{
    return WhereItPlays::at(Location::of(sprintf('https://192.168.1.42:8920/Videos/%s/master.m3u8', $id)), Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)));
}

/** A film the core answers in full. */
function alienInFull(WhereItPlays $plays): ATitle
{
    return ATitle::of(
        Holding::of(HoldingId::called('a1'), 'Alien', Medium::Film, WhenItCameOut::in(1979)),
        ItsDetails::of('A crew meets something on the way home.', HowLongItRuns::minutes(117), Genres::of('Horror', 'Science Fiction'), '16', WhenItWasReleased::on(1979, 5, 25)),
        $plays,
        Seasons::none(),
    );
}

/** A series of one season and two episodes, the second unnumbered and with nowhere to stream from. */
function aSeriesInFull(): ATitle
{
    return ATitle::of(
        Holding::of(HoldingId::called('s1'), 'Slow Horses', Medium::Series, WhenItCameOut::in(2022)),
        ItsDetails::of('', HowLongItRuns::unstated(), Genres::of(), '', WhenItWasReleased::unstated()),
        WhereItPlays::doesNotStream(),
        Seasons::of(ASeason::of('Season 1', Episodes::of(
            AnEpisode::of(HoldingId::called('e1'), 'Failure\'s Contagious', NumberedAs::number(1), HowLongItRuns::minutes(49), 'Jackson Lamb runs Slough House.', streamingAtTheDoor('e1')),
            AnEpisode::of(HoldingId::called('e2'), 'A special', NumberedAs::none(), HowLongItRuns::unstated(), '', WhereItPlays::cannot(Sentence::of('The front door has no certificate yet.'))),
        ))),
    );
}

/** The title's screen at the title the route names, over a fake, signed in as a member unless a test says otherwise. */
function theTitleScreen(AShelfThatWasRead $watching, bool $signedIn = true): WhatThisTitleIs
{
    $stack = theStackATitleIsOn();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::member('ada'));
    }

    $screen = new WhatThisTitleIs($watching, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack), storage: $keychain), new AppsSettingsThatOpen());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => 'a1']);

    return $screen;
}

/** A shelf answering a title with this. */
function aShelfAnsweringTheTitle(WhatTheTitleIs $title): AShelfThatWasRead
{
    return AShelfThatWasRead::holdingNothing()->answeringTheTitle($title);
}

it('asks for the title the route names', function (): void {
    $watching = aShelfAnsweringTheTitle(WhatTheTitleIs::told(alienInFull(streamingAtTheDoor('a1'))));

    theTitleScreen($watching)->title();

    expect($watching->titlesAskedFor())->toBe(['a1']);
});

it('draws the title in full, with Play that waits and the reason beside it', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::told(alienInFull(streamingAtTheDoor('a1'))))));
    $poster = array_search('Alien, Film, 1979', $drawn->said(), strict: true);

    expect($poster)->toBeInt()
        ->and(array_slice($drawn->said(), (int) $poster, 10))->toBe([
            'Alien, Film, 1979', '1979 · Film', 'Alien',
            __('household.title.play'),
            __('household.title.cannot_play'),
            'A crew meets something on the way home.',
            __('household.title.runs_hours', ['hours' => 1, 'minutes' => 57]),
            __('household.title.certificate', ['certificate' => '16']),
            __('household.title.released', ['day' => 25, 'month' => WhatMarkupDraws::words('household.month.may'), 'year' => 1979]),
            'Horror, Science Fiction',
        ])
        ->and($drawn->offersThatWait())->toBe([__('household.title.play')]);
});

it('says why it cannot play in the core\'s own words where no location is stated', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::told(alienInFull(WhereItPlays::cannot(Sentence::of('This machine\'s household address is not known.')))))));

    expect($drawn->said())->toContain('This machine\'s household address is not known.')
        ->and($drawn->said())->not->toContain(__('household.title.cannot_play'))
        ->and($drawn->offersThatWait())->toBe([__('household.title.play')]);
});

it('draws a series with its seasons, each episode with its own Play, and Play as its first episode\'s', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::told(aSeriesInFull()))));

    expect($drawn->said())->toContain('Season 1')
        ->and($drawn->said())->toContain(__('household.title.episode', ['number' => '1', 'title' => 'Failure\'s Contagious']))
        ->and($drawn->said())->toContain(__('household.title.runs_minutes', ['minutes' => 49]))
        ->and($drawn->said())->toContain('Jackson Lamb runs Slough House.')
        ->and($drawn->said())->toContain(__('household.title.episode_unnumbered', ['title' => 'A special']))
        ->and($drawn->said())->toContain('The front door has no certificate yet.')
        ->and($drawn->offersThatWait())->toBe(array_fill(0, 3, __('household.title.play')));
});

it('says there is nothing to play in a series with no episodes', function (): void {
    $empty = ATitle::of(
        Holding::of(HoldingId::called('s2'), 'Coming soon', Medium::Series, WhenItCameOut::unstated()),
        ItsDetails::of('', HowLongItRuns::unstated(), Genres::of(), '', WhenItWasReleased::unstated()),
        WhereItPlays::doesNotStream(),
        Seasons::none(),
    );

    expect(WhatTheDeviceWouldDraw::by(theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::told($empty))))->said())
        ->toContain(__('household.title.nothing_to_play'));
});

it('says a title is not on their shelf where the core answers it as absent, and leads Home', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::absent())));

    expect($drawn->said())->toContain(__('household.title.absent'))
        ->and($drawn->offers())->toBe([__('household.title.to_home')]);
});

it('says what stood in the way, and offers to ask again', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect($drawn->offers())->toContain(__('household.ask_again'));
});

it('asks again after what stood in the way', function (): void {
    $watching = AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = theTitleScreen($watching);

    $screen->title();
    $screen->askAgain();
    $screen->title();

    expect($watching->titlesAskedFor())->toBe(['a1', 'a1']);
});

it('asks nothing and offers to sign in where no session is held', function (): void {
    $watching = aShelfAnsweringTheTitle(WhatTheTitleIs::absent());
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen($watching, signedIn: false));

    expect($watching->titlesAskedFor())->toBe([])
        ->and($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->offers())->toBe([__('connection.sign_in')]);
});

it('is drawn under Home, opened over it, with the bar hidden', function (): void {
    $screen = theTitleScreen(aShelfAnsweringTheTitle(WhatTheTitleIs::absent()));

    expect($screen->itsTab())->toBe(TheHouseholdsTabs::Home)
        ->and($screen->tabBarOptions())->not->toBeNull();
});

it('is the screen the router opens at a title\'s path', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Title->forTheStacksTitle(theStackATitleIsOn()->id(), HoldingId::called('a1')));

    expect($resolved['class'] ?? null)->toBe(WhatThisTitleIs::class);
});

it('says a title is not on their shelf where the route names none, and asks nothing', function (): void {
    $watching = aShelfAnsweringTheTitle(WhatTheTitleIs::told(alienInFull(streamingAtTheDoor('a1'))));
    $screen = theTitleScreen($watching);
    $screen->setParams(['stack' => theStackATitleIsOn()->id()->stored()]);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('household.title.absent'))
        ->and($watching->titlesAskedFor())->toBe([]);
});

it('offers to sign in again where the core refused the session', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theTitleScreen(AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))));

    expect($drawn->said())->toContain(__('connection.session_has_ended'))
        ->and($drawn->offers())->toBe([__('connection.sign_in')]);
});
