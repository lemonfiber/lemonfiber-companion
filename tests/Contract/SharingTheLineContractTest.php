<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\ALift;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\ARateGiven;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SharingTheLine;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatALimitCameTo;
use Modules\Kernel\Api\WhatBecameOfTheLineChange;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhichRate;
use Modules\Sdk\Api\Linesmen;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatSharesItsLine;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The SharingTheLine contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose line is changed. */
function aStackWhoseLineChanges(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a change to its line with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfAChangeToItsLine(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'bandwidth', 'data' => [
        'rehearsed' => true,
        'applied' => false,
        'restraint' => 'limited',
        'means' => 'The stack would take half the line',
        'cautions' => [],
        'untouched' => [],
        'down' => ['limit' => ['as' => 'share', 'at' => 50], 'resolved' => ['is' => 'at', 'bytes_per_second' => 6_250_000], 'says' => 'Half of 100 Mbit/s'],
        'up' => ['limit' => ['as' => 'unlimited'], 'resolved' => ['is' => 'unlimited'], 'says' => 'No limit'],
        'respite' => ['standing' => 'none'],
        'clients' => [
            ['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'asked' => 6_250_000], 'up' => ['verdict' => 'unasked']]],
        ],
        ...$changed,
    ]];
}

/** The rehearsal that payload stands for, as the fake is handed it. */
function theSameRehearsalOfTheLine(): TheLineAsChanged
{
    return TheLineAsChanged::said(
        HowTheLineIsShared::standing(WhereTheLineStands::Limited, 'The stack would take half the line', 'Half of 100 Mbit/s', 'No limit', Remarks::of(), Remarks::of()),
        WhatTheLineRunDid::Rehearsed,
        WhereALiftStands::none(),
        AClientsHolding::answered('qbittorrent', WhatADownloadClientSaid::Unsaid, ADirectionHeld::said(WhatALimitCameTo::Holding, ARateGiven::of(WhichRate::Asked, 6_250_000)), ADirectionHeld::said(WhatALimitCameTo::Unasked)),
    );
}

/**
 * The refusal a stack answers a limit it cannot read with.
 *
 * @return array<string, mixed>
 */
function aLimitTheStackCannotRead(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'RATE-3',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'half could not be read as a download limit',
        'meaning' => 'A limit read wrongly is a household wondering all evening why the calls keep dropping.',
        'remedies' => [['action' => 'Write a download limit as 50%, 2MiB or unlimited']],
    ]];
}

/** The adapter, answered with these responses in turn. */
function aLinesmanAnswered(MockResponse ...$answered): SharingTheLine
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Linesmen(new PinnedClients(), SequencedEntropy::counting());
}

/** What a stack answers a change it took on with, naming the work. */
function aChangeToTheLineTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'bandwidth', 'job' => $job]]), 202);
}

/**
 * Both ways of changing the line, each set up to say the same of the rehearsal.
 *
 * @return array<string, Closure(): SharingTheLine>
 */
function everyWayOfChangingTheLine(MockResponse $answered, WhatBecameOfTheLineChange $became): array
{
    return [
        'the fake' => static fn(): SharingTheLine => AStackThatSharesItsLine::answering($became, $became),
        'the adapter' => static fn(): SharingTheLine => aLinesmanAnswered($answered),
    ];
}

/** What came of something asked, every part folded to one line. */
function whatTheLineChangeCameTo(WhatBecameOfTheLineChange $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(TheLineAsChanged $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%s|%s',
            $report->did()->value,
            $report->line()->downSays(),
            $report->hasClients() ? 'clients' : 'no clients',
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** A change asking for half the line. */
function halfTheLine(): ALineChange
{
    return ALineChange::asked(ALift::none(), down: '50%');
}

it('comes away from rehearsing with the job the stack named', function (): void {
    foreach (everyWayOfChangingTheLine(aChangeToTheLineTakenOn(AStackThatSharesItsLine::THE_REHEARSAL), WhatBecameOfTheLineChange::ended()) as $which => $build) {
        expect(whatTheLineChangeCameTo($build()->rehearse(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), halfTheLine())))
            ->toBe(sprintf('following %s', AStackThatSharesItsLine::THE_REHEARSAL), $which);
    }
});

it('reads what the work came to, the rehearsal labelled as one', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToItsLine()));

    foreach (everyWayOfChangingTheLine($finished, WhatBecameOfTheLineChange::answered(theSameRehearsalOfTheLine())) as $which => $build) {
        expect(whatTheLineChangeCameTo($build()->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSharesItsLine::THE_REHEARSAL))))
            ->toBe('rehearsed|Half of 100 Mbit/s|clients', $which);
    }
});

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'bandwidth', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);
    $stillRunning = aLinesmanAnswered($running)->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named('a-job'));

    expect(whatTheLineChangeCameTo($stillRunning))->toBe('following a-job')
        ->and(whatTheLineChangeCameTo(aLinesmanAnswered($forgotten)->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(aLimitTheStackCannotRead()), 422);
    $said = 'refused: half could not be read as a download limit / Write a download limit as 50%, 2MiB or unlimited';

    expect(whatTheLineChangeCameTo(aLinesmanAnswered($refused)->rehearse(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), ALineChange::asked(ALift::none(), down: 'half'))))->toBe($said)
        ->and(whatTheLineChangeCameTo(aLinesmanAnswered($refused)->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatTheLineChangeCameTo(aLinesmanAnswered($answered)->carryOut(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), halfTheLine())))->toBe($kind->name)
        ->and(whatTheLineChangeCameTo(aLinesmanAnswered($answered)->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatTheLineChangeCameTo(AStackThatSharesItsLine::sayingAtOnce(WhatBecameOfTheLineChange::met(Obstacle::of($kind)))->carryOut(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), halfTheLine())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads work that came back unreadable as an answer it could not read, never a shorter one', function (array $changed): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToItsLine($changed)));

    expect(whatTheLineChangeCameTo(aLinesmanAnswered($finished)->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'a client with no answer' => [['clients' => [['client' => 'qbittorrent']]]],
    'a figure below nothing' => [['clients' => [['client' => 'qbittorrent', 'answer' => ['answered' => 'held', 'down' => ['verdict' => 'holding', 'asked' => -1], 'up' => ['verdict' => 'holding']]]]]],
    'a line with no meaning' => [['means' => ' ']],
]);

it('reads a handle it cannot follow as an answer it could not read', function (): void {
    expect(whatTheLineChangeCameTo(aLinesmanAnswered(aChangeToTheLineTakenOn(' '))->carryOut(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), halfTheLine())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

/**
 * Where a recorded request went and what it carried.
 *
 * @return array<mixed>
 */
function whatWasSentToTheLine(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        ...(is_array($body) ? $body : []),
    ];
}

it('sends every part as written, the rehearsal as a dry run and the yes without one', function (): void {
    $linesmen = aLinesmanAnswered(aChangeToTheLineTakenOn('a'), aChangeToTheLineTakenOn('b'));
    $change = ALineChange::asked(ALift::forMinutes(60), '50%', '1MiB', '07:00-23:00', '60MiB/6MiB', '500GiB', 'pause');

    $linesmen->rehearse(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), $change);
    $linesmen->carryOut(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), $change);

    [$rehearsal, $yes] = array_map(whatWasSentToTheLine(...), MockClient::global()->getRecordedResponses());
    $parts = ['down' => '50%', 'up' => '1MiB', 'active' => '07:00-23:00', 'line' => '60MiB/6MiB', 'cap' => '500GiB', 'exceeded' => 'pause', 'unrestricted_for' => 60];

    expect($rehearsal)->toMatchArray(['to' => '/api/actions/bandwidth', 'dry_run' => true, ...$parts])
        ->and($yes)->toMatchArray(['to' => '/api/actions/bandwidth', ...$parts])
        ->and($yes['dry_run'] ?? false)->toBeFalse();
});

it('the fake remembers each change rehearsed and carried out, and each handle followed', function (): void {
    $sharing = AStackThatSharesItsLine::answering(WhatBecameOfTheLineChange::answered(theSameRehearsalOfTheLine()), WhatBecameOfTheLineChange::ended());
    $change = halfTheLine();
    $sharing->rehearse(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), $change);
    $sharing->carryOut(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), $change);
    $became = $sharing->whatBecameOf(aStackWhoseLineChanges(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSharesItsLine::THE_CHANGE));

    expect($sharing->rehearsed())->toBe([$change])
        ->and($sharing->carriedOut())->toBe([$change])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $sharing->followed()))->toBe([AStackThatSharesItsLine::THE_CHANGE])
        ->and(whatTheLineChangeCameTo($became))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BandwidthEnvelope', whatAStackSaysOfAChangeToItsLine()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aLimitTheStackCannotRead()))
        ->toBe([], "The refusal this suite stands in for a stack with is not one a stack would send.\n");
});
