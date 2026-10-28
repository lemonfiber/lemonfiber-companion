<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASessionPlaying;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FollowingWhatIsPlaying;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Players;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatPlays;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A house whose operator watches what is playing. */
function aHouseThatPlays(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('p', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.44:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function theOperatorsSessionWhilePlaying(): Session
{
    return Session::of('an-operator-session-not-a-secret');
}

/**
 * What the core says is playing, changed where a case says.
 *
 * @param  array<mixed>  $changed the report's own fields that differ
 * @return array<string, mixed>
 */
function whatTheCoreSaysIsPlaying(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'playing', 'data' => [
        'member' => '',
        'available' => true,
        'findings' => [],
        'sessions' => [
            ['member' => 'Sanne', 'member_id' => 'u-1', 'title' => 'Low Tide', 'device' => 'Living room TV', 'medium' => 'film', 'paused' => false],
            ['member' => 'Joris', 'member_id' => 'u-2', 'title' => 'The Lighthouse', 'device' => 'iPad', 'medium' => 'episode', 'paused' => true, 'series' => 'Harbour Lights', 'season' => 1, 'episode' => 3],
            ['member' => 'Joris', 'member_id' => 'u-2', 'title' => 'Pilot', 'device' => 'Phone', 'medium' => 'episode', 'paused' => false, 'series' => 'Harbour Lights', 'season' => null, 'episode' => null],
        ],
        ...$changed,
    ]];
}

/** What that report stands for, as the fake is handed it. */
function theSameThingPlaying(): WhatTheHouseholdIsPlaying
{
    return WhatTheHouseholdIsPlaying::these(
        ASessionPlaying::of('Sanne', 'Low Tide', 'Living room TV', paused: false),
        ASessionPlaying::ofANumberedEpisode('Joris', 'The Lighthouse', 'iPad', paused: true, series: 'Harbour Lights', season: 1, episode: 3),
        ASessionPlaying::ofAnEpisode('Joris', 'Pilot', 'Phone', paused: false, series: 'Harbour Lights'),
    );
}

/**
 * One `playing` event, as the core frames it on the stream.
 *
 * @param  array<string, mixed>  $envelope
 */
function aPlayingEvent(array $envelope): string
{
    return sprintf("id: run-1\nevent: playing\ndata: %s\n\n", json_encode($envelope, JSON_THROW_ON_ERROR));
}

/**
 * Both ways of following what is playing, each set up to say the same.
 *
 * @param  list<MockResponse>                                $answered what the far end answers, in order
 * @param  list<WhatAStreamSaid<WhatTheHouseholdIsPlaying>>  $script   what the fake hears, wake by wake
 * @return array<string, Closure(): FollowingWhatIsPlaying>
 */
function everyWayOfFollowingWhatIsPlaying(array $answered, HowPlayingWasRead $now, array $script): array
{
    return [
        'the fake' => static fn(): FollowingWhatIsPlaying => AStackThatPlays::thenEnding($now, ...$script),
        'the adapter' => static function () use ($answered): FollowingWhatIsPlaying {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Players(new PinnedClients());
        },
    ];
}

/** What is playing, every part folded to one line. */
function whatIsPlayingAsAWord(WhatTheHouseholdIsPlaying $playing): string
{
    if (! $playing->wasRead()) {
        return sprintf('unread: %s', $playing->whyNot());
    }

    $sessions = [];

    foreach ($playing as $session) {
        $sessions[] = sprintf('%s on %s%s: %s', $session->member(), $session->device(), $session->isPaused() ? ' (paused)' : '', $session->what(
            alone: static fn(string $title): TheWordCarriedOut => new TheWordCarriedOut($title),
            inSeries: static fn(string $series, string $title): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s / %s', $series, $title)),
            numbered: static fn(string $series, int $season, int $episode, string $title): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s S%dE%d / %s', $series, $season, $episode, $title)),
        )->said);
    }

    return $sessions === [] ? 'nothing' : implode('; ', $sessions);
}

function howPlayingWasReadAsAWord(HowPlayingWasRead $read): string
{
    return $read->either(
        said: static fn(WhatTheHouseholdIsPlaying $playing): TheWordCarriedOut => new TheWordCarriedOut(whatIsPlayingAsAWord($playing)),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** @param WhatAStreamSaid<WhatTheHouseholdIsPlaying> $said */
function whatTheStreamSaidOfPlaying(WhatAStreamSaid $said): string
{
    return $said->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(WhatTheHouseholdIsPlaying $playing): TheWordCarriedOut => new TheWordCarriedOut(whatIsPlayingAsAWord($playing)),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

const EVERYTHING_PLAYING = 'Sanne on Living room TV: Low Tide; Joris on iPad (paused): Harbour Lights S1E3 / The Lighthouse; Joris on Phone: Harbour Lights / Pilot';

it('reads every session the core says is playing, an episode with its series and numbers', function (): void {
    foreach (everyWayOfFollowingWhatIsPlaying(
        [MockResponse::make(whatTheCoreSaysIsPlaying())],
        HowPlayingWasRead::said(theSameThingPlaying()),
        [],
    ) as $which => $make) {
        expect(howPlayingWasReadAsAWord($make()->now(aHouseThatPlays(), theOperatorsSessionWhilePlaying())))->toBe(EVERYTHING_PLAYING, $which);
    }
});

it('reads no sessions as nothing playing, and a media server it could not read as unread', function (): void {
    $table = [
        [whatTheCoreSaysIsPlaying(['sessions' => []]), WhatTheHouseholdIsPlaying::these(), 'nothing'],
        [whatTheCoreSaysIsPlaying(['available' => false, 'sessions' => [], 'findings' => ['Jellyfin did not answer.', 'Try again shortly.']]), WhatTheHouseholdIsPlaying::unread('Jellyfin did not answer.', 'Try again shortly.'), 'unread: Jellyfin did not answer. Try again shortly.'],
    ];

    foreach ($table as [$payload, $playing, $word]) {
        foreach (everyWayOfFollowingWhatIsPlaying([MockResponse::make($payload)], HowPlayingWasRead::said($playing), []) as $which => $make) {
            expect(howPlayingWasReadAsAWord($make()->now(aHouseThatPlays(), theOperatorsSessionWhilePlaying())))->toBe($word, $which);
        }
    }
});

it('hears what is playing on the stream, and then that the stream ended', function (): void {
    foreach (everyWayOfFollowingWhatIsPlaying(
        [MockResponse::make(aPlayingEvent(whatTheCoreSaysIsPlaying()))],
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these()),
        [WhatAStreamSaid::said(theSameThingPlaying())],
    ) as $which => $make) {
        $following = $make();

        expect([
            whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying())),
            whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying())),
        ])->toBe([EVERYTHING_PLAYING, 'closed'], $which);
    }
});

it('hears nothing from a stream that has said nothing yet, and then that it closed', function (): void {
    foreach (everyWayOfFollowingWhatIsPlaying(
        [MockResponse::make('')],
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these()),
        [WhatAStreamSaid::nothing(), WhatAStreamSaid::closed()],
    ) as $which => $make) {
        $following = $make();

        expect([
            whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying())),
            whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying())),
        ])->toBe(['nothing', 'closed'], $which);
    }
});

it('hears a sign of life in a heartbeat, and closes when let go of', function (): void {
    foreach (everyWayOfFollowingWhatIsPlaying(
        [MockResponse::make(": beat\n\n")],
        HowPlayingWasRead::said(WhatTheHouseholdIsPlaying::these()),
        [WhatAStreamSaid::aSignOfLife()],
    ) as $which => $make) {
        $following = $make();

        expect(whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying())))->toBe('alive', $which)
            ->and(whatTheStreamSaidOfPlaying($following->letGo()))->toBe('closed', $which);
    }
});

it('says a refused session as refused, and a report it cannot read as an answer it could not read', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
        [MockResponse::make(whatTheCoreSaysIsPlaying(['sessions' => [['member' => 'Sanne', 'title' => 'Low Tide', 'medium' => 'film', 'paused' => false, 'member_id' => 'u-1']]])), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make(whatTheCoreSaysIsPlaying(['sessions' => [['member' => '  ', 'title' => 'Low Tide', 'device' => 'TV', 'medium' => 'film', 'paused' => false, 'member_id' => 'u-1']]])), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make(whatTheCoreSaysIsPlaying(['available' => 'yes'])), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make(whatTheCoreSaysIsPlaying(['available' => false, 'sessions' => [], 'findings' => 'Jellyfin did not answer.'])), KindOfObstacle::AnswerCouldNotBeRead],
        [MockResponse::make(whatTheCoreSaysIsPlaying(['sessions' => [['member' => 'Sanne', 'title' => 'Low Tide', 'device' => 'TV', 'medium' => 'film', 'paused' => 'no', 'member_id' => 'u-1']]])), KindOfObstacle::AnswerCouldNotBeRead],
    ];

    foreach ($table as [$answered, $kind]) {
        foreach (everyWayOfFollowingWhatIsPlaying([$answered], HowPlayingWasRead::refused(Obstacle::of($kind)), []) as $which => $make) {
            expect(howPlayingWasReadAsAWord($make()->now(aHouseThatPlays(), theOperatorsSessionWhilePlaying())))->toBe($kind->value, sprintf('%s, %s', $which, $kind->value));
        }
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PlayingEnvelope', whatTheCoreSaysIsPlaying()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

/**
 * What the adapter hears across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesOfPlayingHear(Players $following, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = whatTheStreamSaidOfPlaying($following->heard(aHouseThatPlays(), theOperatorsSessionWhilePlaying()));
    }

    return $heard;
}

it('opens a stream that ended again, once it has said that it ended, from where it left off', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aPlayingEvent(whatTheCoreSaysIsPlaying())),
        MockResponse::make(aPlayingEvent(whatTheCoreSaysIsPlaying(['sessions' => []]))),
    ]);

    expect(whatWakesOfPlayingHear(new Players(new PinnedClients()), 3))->toBe([EVERYTHING_PLAYING, 'closed', 'nothing'])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('opens again the next time it is asked once let go of, forgetting where the stream left off', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aPlayingEvent(whatTheCoreSaysIsPlaying())),
        MockResponse::make(aPlayingEvent(whatTheCoreSaysIsPlaying(['sessions' => []]))),
    ]);
    $following = new Players(new PinnedClients());

    expect(whatWakesOfPlayingHear($following, 1))->toBe([EVERYTHING_PLAYING])
        ->and(whatTheStreamSaidOfPlaying($following->letGo()))->toBe('closed')
        ->and(whatWakesOfPlayingHear($following, 1))->toBe(['nothing'])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, null]);
});
