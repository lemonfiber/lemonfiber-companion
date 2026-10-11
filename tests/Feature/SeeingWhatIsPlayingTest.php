<?php

declare(strict_types=1);

use Modules\Health\Internal\TheHeartbeat;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASessionPlaying;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatIsPlayingNow;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\WhereInTheMenu;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatPlays;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

function theHouseWhosePlayingIsWatched(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The den'),
        Address::of('https://192.168.1.45:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

function secondsIntoWatchingWhatPlays(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/** The screen, opened with the session given and the stack answering as scripted. */
function whatIsPlayingOpened(AStackThatPlays $playing, ?Whose $whose = null, ?FrozenClock $clock = null, ?ACaptureInMemory $capture = null, ?AKeychainInMemory $keychain = null): WhatIsPlayingNow
{
    $stack = theHouseWhosePlayingIsWatched();
    $keychain ??= AKeychainInMemory::working();

    if ($whose instanceof Whose) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), $whose);
    }

    $screen = new WhatIsPlayingNow(
        $playing,
        $keychain,
        $clock ?? FrozenClock::at(secondsIntoWatchingWhatPlays(0)),
        $capture ?? ACaptureInMemory::inFront(),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        settings: new AppsSettingsThatOpen(),
        listening: AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

function sannesFilm(): ASessionPlaying
{
    return ASessionPlaying::of('Sanne', 'Low Tide', 'Living room TV', paused: false);
}

function jorisEpisode(): ASessionPlaying
{
    return ASessionPlaying::ofANumberedEpisode('Joris', 'The Lighthouse', 'iPad', paused: true, series: 'Harbour Lights', season: 1, episode: 3);
}

/** @param array<string, string> $filling */
function aLinePlaying(string $key, array $filling = []): string
{
    $line = __($key, $filling);

    return is_string($line) ? $line : '';
}

it('opens on what the core says is playing: who, what, on which device, and whether it is paused', function (): void {
    $screen = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(sannesFilm(), jorisEpisode()))), Whose::theOperator());

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
        aLinePlaying('stacks.playing.heading'),
        'Sanne',
        'Low Tide',
        aLinePlaying('stacks.playing.on', ['device' => 'Living room TV', 'state' => aLinePlaying('stacks.playing.playing')]),
        'Joris',
        aLinePlaying('stacks.playing.numbered', ['series' => 'Harbour Lights', 'season' => '1', 'episode' => '3', 'title' => 'The Lighthouse']),
        aLinePlaying('stacks.playing.on', ['device' => 'iPad', 'state' => aLinePlaying('stacks.playing.paused')]),
    );
});

it('names the series of an episode the media server did not number', function (): void {
    $screen = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(
        ASessionPlaying::ofAnEpisode('Joris', 'Pilot', 'Phone', paused: false, series: 'Harbour Lights'),
    ))), Whose::theOperator());

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLinePlaying('stacks.playing.in_series', ['series' => 'Harbour Lights', 'title' => 'Pilot']));
});

it('says plainly when nothing is playing, and that a media server it could not read is not nothing', function (): void {
    $nothing = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these())), Whose::theOperator());
    $unread = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::unread('Jellyfin did not answer.'))), Whose::theOperator());

    expect(WhatTheDeviceWouldDraw::by($nothing)->said())->toContain(aLinePlaying('stacks.playing.nothing'))
        ->and(WhatTheDeviceWouldDraw::by($unread)->said())->toContain(aLinePlaying('stacks.playing.unread'), 'Jellyfin did not answer.')
        ->and(WhatTheDeviceWouldDraw::by($unread)->said())->not->toContain(aLinePlaying('stacks.playing.nothing'));
});

it('keeps the list current from the stream without being asked again', function (): void {
    $playing = AStackThatPlays::holdingOpen(
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(sannesFilm())),
        WhatAStreamSaid::said(WhatTheHouseholdIsPlaying::these(jorisEpisode())),
    );
    $screen = whatIsPlayingOpened($playing, Whose::theOperator());

    $screen->follow();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($playing->asked())->toBe(1)
        ->and($drawn)->toContain('Joris')
        ->and($drawn)->not->toContain('Sanne')
        ->and($screen->answer()->ago->said)->toBe('');
});

it('says when it last heard once the stream stops, and that it could not hear', function (): void {
    $clock = FrozenClock::at(secondsIntoWatchingWhatPlays(0));
    $playing = AStackThatPlays::holdingOpen(
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(sannesFilm())),
        WhatAStreamSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
    );
    $screen = whatIsPlayingOpened($playing, Whose::theOperator(), clock: $clock);

    $clock->moveTo(secondsIntoWatchingWhatPlays(120));
    $screen->follow();

    expect($screen->answer()->broke)->toBeTrue()
        ->and($screen->answer()->ago->said)->not->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLinePlaying('stacks.playing.unheard'), 'Sanne');
});

it('lets go of a stream gone quiet past the contract\'s silence, and of one nobody can see', function (): void {
    $clock = FrozenClock::at(secondsIntoWatchingWhatPlays(0));
    $capture = ACaptureInMemory::inFront();
    $playing = AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(sannesFilm())));
    $screen = whatIsPlayingOpened($playing, Whose::theOperator(), clock: $clock, capture: $capture);

    $screen->follow();
    $clock->moveTo(secondsIntoWatchingWhatPlays(TheHeartbeat::silenceAllowed()->inSeconds() + 1));
    $screen->follow();

    expect($playing->lettingsGo())->toBe(1)
        ->and($screen->answer()->ago->said)->not->toBe('');

    $capture->backgrounded();
    $screen->follow();

    expect($playing->lettingsGo())->toBe(2)
        ->and($playing->asked())->toBe(2);
});

it('waits out a break before opening the stream again', function (): void {
    $clock = FrozenClock::at(secondsIntoWatchingWhatPlays(0));
    $playing = AStackThatPlays::holdingOpen(
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these()),
        WhatAStreamSaid::closed(),
    );
    $screen = whatIsPlayingOpened($playing, Whose::theOperator(), clock: $clock);

    $screen->follow();
    $clock->moveTo(secondsIntoWatchingWhatPlays(HowOftenAScreenLooks::AfterABreak->seconds() - 1));
    $screen->follow();

    expect($playing->asked())->toBe(1);

    $clock->moveTo(secondsIntoWatchingWhatPlays(HowOftenAScreenLooks::AfterABreak->seconds()));
    $screen->follow();

    expect($playing->asked())->toBe(2);
});

it('never asks with a member\'s session, and tells a member it is not theirs to see', function (): void {
    $playing = AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these(sannesFilm())), WhatAStreamSaid::said(WhatTheHouseholdIsPlaying::these(sannesFilm())));
    $screen = whatIsPlayingOpened($playing, Whose::member('a7f3'));

    $screen->follow();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($playing->asked())->toBe(0)
        ->and($drawn)->toContain(aLinePlaying('stacks.playing.operators_only'))
        ->and($drawn)->not->toContain('Sanne');
});

it('offers signing in where the device holds no session, and lets go of one the stack refused', function (): void {
    $signedOut = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these())));
    $keychain = AKeychainInMemory::working();
    $refused = whatIsPlayingOpened(AStackThatPlays::holdingOpen(HowPlayingWasRead::refused(Obstacle::of(KindOfObstacle::CredentialWasRefused))), Whose::theOperator(), keychain: $keychain);

    expect($signedOut->answer()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($signedOut)->said())->toContain(aLinePlaying('connection.session_has_ended'))
        ->and($refused->answer()->went->cameBack())->toBeFalse()
        ->and($keychain->resume(theHouseWhosePlayingIsWatched()->id())->whoseItIs(
            nobody: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nobody'),
            theirs: static fn(): TheWordCarriedOut => new TheWordCarriedOut('somebody'),
        )->said)->toBe('nobody');
});

it('is reached from the household part of the operator\'s menu', function (): void {
    expect(TheMenu::PlayingNow->screen())->toBe(AStacksScreen::Playing)
        ->and(TheMenu::PlayingNow->group())->toBe(WhereInTheMenu::Household);
});
