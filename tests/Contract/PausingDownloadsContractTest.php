<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowPausingIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PausingDownloads;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Pausers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatPausesDownloads;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose downloads are paused. */
function aStackThatFetches(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers about pausing its downloads, changed where a case says.
 *
 * @param  array<mixed>             $changed the report's own fields that differ
 * @param  array<mixed>|null        $clients the clients, where they differ
 * @return array<string, mixed>
 */
function whatAStackSaysOfPausing(array $changed = [], ?array $clients = null): array
{
    return ['api_version' => 1, 'kind' => 'pausing', 'data' => [
        'asked' => 'pause',
        'rehearsed' => true,
        'offer' => 'pause-qbittorrent-fetching-sabnzbd-stopped',
        'caution' => null,
        'clients' => $clients ?? [
            ['client' => 'qbittorrent', 'was' => 'fetching'],
            ['client' => 'sabnzbd', 'was' => 'stopped', 'now' => null, 'unreached' => null],
            ['client' => 'nzbget', 'unreached' => 'connection refused'],
        ],
        ...$changed,
    ]];
}

/** The rehearsal that payload stands for, as the fake is handed it. */
function theSameRehearsalOfAPause(): WhatPausingComesTo
{
    return WhatPausingComesTo::said(
        WhatToDoWithTheDownloads::Pause,
        WhetherItWasRehearsed::Rehearsed,
        'pause-qbittorrent-fetching-sabnzbd-stopped',
        '',
        ADownloadClientAsked::reached('qbittorrent', WhatADownloadClientSaid::Fetching, WhatADownloadClientSaid::Unsaid),
        ADownloadClientAsked::reached('sabnzbd', WhatADownloadClientSaid::Stopped, WhatADownloadClientSaid::Unsaid),
        ADownloadClientAsked::unreached('nzbget', WhatADownloadClientSaid::Unsaid, 'connection refused'),
    );
}

/**
 * Both ways of pausing, each set up to say the same.
 *
 * @return array<string, Closure(): PausingDownloads>
 */
function everyWayOfPausing(MockResponse $answered, HowPausingIsGoing $offer, HowPausingIsGoing $became): array
{
    return [
        'the fake' => static fn(): PausingDownloads => AStackThatPausesDownloads::rehearsing($offer, $became),
        'the adapter' => static fn(): PausingDownloads => aPauserAnswered($answered),
    ];
}

/** The adapter, answered with one response. */
function aPauserAnswered(MockResponse $answered): PausingDownloads
{
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    return new Pausers(new PinnedClients(), SequencedEntropy::counting());
}

/** What a stack answers a request it took on with, naming the work. */
function aPauseTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'downloads-pause', 'job' => $job]]), 202);
}

/**
 * Where a recorded request went, and whether it rehearsed or carried an offer.
 *
 * @return array{to: string, dry_run: bool, offer: mixed}
 */
function whatWasSentToPause(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();
    $said = is_array($body) ? $body : [];

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        'dry_run' => ($said['dry_run'] ?? false) === true,
        'offer' => $said['offer'] ?? null,
    ];
}

/** What rehearsing came to, as a line. */
function howRehearsingAPauseWent(PausingDownloads $pausing, WhatToDoWithTheDownloads $asked = WhatToDoWithTheDownloads::Pause): string
{
    return $pausing->rehearse(aStackThatFetches(), Session::of('a-session-not-a-secret'), $asked)->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What the yes came to, as a line. */
function howAPauseWasAgreed(PausingDownloads $pausing): string
{
    return $pausing->carryOut(aStackThatFetches(), Session::of('a-session-not-a-secret'), theSameRehearsalOfAPause())->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What became of a job, every part folded to one line. */
function whatBecameOfPausing(PausingDownloads $pausing, string $job = AStackThatPausesDownloads::THE_REHEARSAL): string
{
    return $pausing->whatBecameOf(aStackThatFetches(), Session::of('a-session-not-a-secret'), Job::named($job))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        answered: static fn(WhatPausingComesTo $report): TheWordCarriedOut => new TheWordCarriedOut(whatThePauseSaid($report)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** A report, every part folded to one line. */
function whatThePauseSaid(WhatPausingComesTo $report): string
{
    $clients = [];

    foreach ($report as $client) {
        $clients[] = $client->wasReached()
            ? sprintf('%s:%s>%s', $client->client(), $client->was()->value, $client->now()->value)
            : sprintf('%s:%s!%s', $client->client(), $client->was()->value, $client->whyItWasNotReached());
    }

    return sprintf('%s|%s|%s|%s|%s', $report->asked()->value, $report->wasRehearsed()->value, $report->agreement(), $report->caution(), implode(',', $clients));
}

it('comes away from rehearsing either way with the job the stack named', function (WhatToDoWithTheDownloads $asked): void {
    foreach (everyWayOfPausing(aPauseTakenOn(AStackThatPausesDownloads::THE_REHEARSAL), HowPausingIsGoing::stillRunning(), HowPausingIsGoing::stillRunning()) as $which => $build) {
        expect(howRehearsingAPauseWent($build(), $asked))->toBe(sprintf('following %s', AStackThatPausesDownloads::THE_REHEARSAL), $which);
    }
})->with([
    'a pause' => [WhatToDoWithTheDownloads::Pause],
    'a resume' => [WhatToDoWithTheDownloads::Resume],
]);

it('comes away from rehearsing with the obstacle rather than a job where the stack would not take it on', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"nothing runs"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [aPauseTakenOn(' '), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        expect(howRehearsingAPauseWent(aPauserAnswered($answered)))->toBe($why->kind()->name)
            ->and(howRehearsingAPauseWent(AStackThatPausesDownloads::met($why)))->toBe($why->kind()->name);
    }
});

it('reads every part of the rehearsal: what was asked, its offer, and each client, one it could not reach named with why', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfPausing()));

    foreach (everyWayOfPausing($finished, HowPausingIsGoing::answered(theSameRehearsalOfAPause()), HowPausingIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfPausing($build()))
            ->toBe('pause|rehearsed|pause-qbittorrent-fetching-sabnzbd-stopped||qbittorrent:fetching>unsaid,sabnzbd:stopped>unsaid,nzbget:unsaid!connection refused', $which);
    }
});

it('reads what each client read back after a yes, and the caution a resume carries', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfPausing(
        ['asked' => 'resume', 'rehearsed' => false, 'caution' => 'The monthly cap is spent, so they stop again'],
        [['client' => 'qbittorrent', 'was' => 'stopped', 'now' => 'fetching'], ['client' => 'nzbget', 'was' => 'stopped', 'unreached' => '']],
    )));

    expect(whatBecameOfPausing(aPauserAnswered($finished), AStackThatPausesDownloads::THE_JOB))
        ->toBe('resume|carried_out|pause-qbittorrent-fetching-sabnzbd-stopped|The monthly cap is spent, so they stop again|qbittorrent:stopped>fetching,nzbget:stopped!');
});

it('reads a stack running no download client as having none', function (): void {
    $report = aPauserAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfPausing(clients: []))))
        ->whatBecameOf(aStackThatFetches(), Session::of('a-session-not-a-secret'), Job::named(AStackThatPausesDownloads::THE_REHEARSAL))
        ->either(
            stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
            answered: static fn(WhatPausingComesTo $said): TheWordCarriedOut => new TheWordCarriedOut($said->hasClients() ? 'some' : 'none'),
            ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
            met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
        );

    expect($report->said)->toBe('none');
});

/**
 * @param array<mixed>     $changed
 * @param array<mixed>|null $clients
 */
function aRehearsalItCannotRead(array $changed, ?array $clients): string
{
    return whatBecameOfPausing(aPauserAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfPausing($changed, $clients)))));
}

it('a rehearsal this app cannot read is an answer it could not read, never a shorter one', function (array $changed, ?array $clients): void {
    expect(aRehearsalItCannotRead($changed, $clients))
        ->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'a blank offer' => [['offer' => ' '], null],
    'no offer' => [['offer' => null], null],
    'no word for what was asked' => [['asked' => null], null],
    'a word for what was asked this app does not know' => [['asked' => 'throttle'], null],
    'no saying whether it was rehearsed' => [['rehearsed' => null], null],
    'a caution that is not words' => [['caution' => 7], null],
    'clients that are not a list' => [['clients' => 'qbittorrent'], null],
    'a client that is not one' => [[], ['qbittorrent']],
    'a client named nothing' => [[], [['client' => ' ', 'was' => 'fetching']]],
    'a client with no name' => [[], [['was' => 'fetching']]],
    'a state this app has no word for' => [[], [['client' => 'qbittorrent', 'was' => 'seeding']]],
    'a state said as unsaid' => [[], [['client' => 'qbittorrent', 'was' => 'unsaid']]],
    'a state that is not words' => [[], [['client' => 'qbittorrent', 'now' => true]]],
    'a reason that is not words' => [[], [['client' => 'qbittorrent', 'unreached' => 3]]],
]);

it('an answer that is not a rehearsal at all is an answer it could not read', function (): void {
    expect(whatBecameOfPausing(aPauserAnswered(MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'pausing', 'data' => 'nothing'])))))
        ->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('a job still running is its own answer', function (): void {
    foreach (everyWayOfPausing(aPauseTakenOn(AStackThatPausesDownloads::THE_REHEARSAL), HowPausingIsGoing::stillRunning(), HowPausingIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfPausing($build()))->toBe('still running', $which);
    }
});

it('a job the stack no longer has is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfPausing($forgotten, HowPausingIsGoing::ended(), HowPausingIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfPausing($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'downloads-pause', 'job' => AStackThatPausesDownloads::THE_REHEARSAL]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after a job tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"still asking"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfPausing($answered, HowPausingIsGoing::met($why), HowPausingIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfPausing($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('comes away from a yes with the job the stack named', function (): void {
    foreach (everyWayOfPausing(aPauseTakenOn(AStackThatPausesDownloads::THE_JOB), HowPausingIsGoing::stillRunning(), HowPausingIsGoing::stillRunning()) as $which => $build) {
        expect(howAPauseWasAgreed($build()))->toBe(sprintf('following %s', AStackThatPausesDownloads::THE_JOB), $which);
    }
});

it('comes away from a refused yes with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"a client moved"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [aPauseTakenOn(' '), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        expect(howAPauseWasAgreed(aPauserAnswered($answered)))->toBe($why->kind()->name)
            ->and(howAPauseWasAgreed(AStackThatPausesDownloads::rehearsingButRefusing(theSameRehearsalOfAPause(), $why)))->toBe($why->kind()->name);
    }
});

it('sends the yes as the action that was rehearsed, carrying the offer, and the rehearsal as a dry run carrying none', function (WhatToDoWithTheDownloads $asked): void {
    MockClient::destroyGlobal();
    MockClient::global([aPauseTakenOn('a'), aPauseTakenOn('b')]);
    $pausers = new Pausers(new PinnedClients(), SequencedEntropy::counting());
    $offer = WhatPausingComesTo::said($asked, WhetherItWasRehearsed::Rehearsed, 'the-offer-named', '', ADownloadClientAsked::reached('qbittorrent', WhatADownloadClientSaid::Fetching, WhatADownloadClientSaid::Unsaid));

    $pausers->rehearse(aStackThatFetches(), Session::of('a-session-not-a-secret'), $asked);
    $pausers->carryOut(aStackThatFetches(), Session::of('a-session-not-a-secret'), $offer);

    [$rehearsal, $yes] = array_map(whatWasSentToPause(...), MockClient::global()->getRecordedResponses());

    expect($rehearsal['to'])->toBe(sprintf('/api/actions/%s', $asked->asked()))
        ->and($rehearsal['dry_run'])->toBeTrue()
        ->and($rehearsal['offer'])->toBeNull()
        ->and($yes['to'])->toBe(sprintf('/api/actions/%s', $asked->asked()))
        ->and($yes['offer'])->toBe('the-offer-named')
        ->and($yes['dry_run'])->toBeFalse();
})->with([
    'a pause' => [WhatToDoWithTheDownloads::Pause],
    'a resume' => [WhatToDoWithTheDownloads::Resume],
]);

it('the fake remembers the direction rehearsed, the offer agreed to and the handles followed', function (): void {
    $pausing = AStackThatPausesDownloads::rehearsing(HowPausingIsGoing::answered(theSameRehearsalOfAPause()), HowPausingIsGoing::stillRunning());
    howRehearsingAPauseWent($pausing, WhatToDoWithTheDownloads::Resume);
    whatBecameOfPausing($pausing);
    howAPauseWasAgreed($pausing);
    whatBecameOfPausing($pausing, AStackThatPausesDownloads::THE_JOB);

    expect($pausing->rehearsed())->toBe([WhatToDoWithTheDownloads::Resume])
        ->and(array_map(static fn(WhatPausingComesTo $offer): string => $offer->agreement(), $pausing->agreed()))->toBe(['pause-qbittorrent-fetching-sabnzbd-stopped'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $pausing->followed()))->toBe([AStackThatPausesDownloads::THE_REHEARSAL, AStackThatPausesDownloads::THE_JOB]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PausingEnvelope', whatAStackSaysOfPausing()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
