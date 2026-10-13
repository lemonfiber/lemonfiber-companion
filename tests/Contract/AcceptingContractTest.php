<?php

declare(strict_types=1);

use Modules\Kernel\Api\Accepting;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhereAChoiceStands;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\Acceptors;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatAccepts;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWhoseWarningIsAccepted(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * The report a stack answers an acceptance with, its one warning in the state a case says.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOnceAWarningIsAccepted(string $state = 'suppressed'): array
{
    return ['api_version' => 1, 'kind' => 'doctor', 'data' => [
        'overall' => 'degraded',
        'rehearsed' => false,
        'findings' => [[
            'check' => 'vpn.contained',
            'category' => 'vpn',
            'title' => 'Torrents run inside a VPN',
            'origin' => ['origin' => 'bundled'],
            'verdict' => [
                'outcome' => 'warn',
                'code' => 'VPN-9',
                'severity' => 'warning',
                'state' => $state,
                'summary' => 'Torrents run with no VPN around them',
                'meaning' => 'Your address is visible to everybody you share with.',
                'remedies' => [['action' => 'Add a VPN provider']],
            ],
        ]],
    ]];
}

function theSameAcceptedReport(): Report
{
    return Report::of(Overall::Degraded, Findings::of(Finding::of(
        Check::of('vpn.contained'),
        Category::Vpn,
        'Torrents run inside a VPN',
        Conclusion::Warned,
        WhatTheCheckSaid::wentWrong(Code::of('VPN-9'), 'Your address is visible to everybody you share with.', Remedies::none(), Severity::Warning, Standing::Suppressed, WhatItSaysUnderneath::none()),
        WhoPutItThere::bundled(),
    )));
}

/** @return array<string, mixed> */
function aWarningTheStackIsNotRaising(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'ACK-1',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'Nothing in this run warns about vpn.contained',
        'meaning' => 'An answer is only meaningful against something the tool is currently saying.',
        'remedies' => [['action' => 'Answer one of the warnings this run raised']],
    ]];
}

function anAcceptorAnswered(MockResponse ...$answered): Accepting
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Acceptors(new PinnedClients(), SequencedEntropy::counting());
}

function anAcceptanceTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'accept', 'job' => $job]]), 202);
}

/**
 * @param WhatBecameOfTheWork<Report> $became
 */
function whatAcceptingCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static function (Report $report): TheWordCarriedOut {
            $said = [];

            foreach ($report->findings() as $finding) {
                $said[] = sprintf('%s=%s', $finding->check()->shown(), WhereAChoiceStands::of($finding)->name);
            }

            return new TheWordCarriedOut(implode(' ', $said));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('comes away from accepting with the job the stack named, and sends the check it was given', function (): void {
    foreach (['the fake' => AStackThatAccepts::answering(WhatBecameOfTheWork::ended()), 'the adapter' => anAcceptorAnswered(anAcceptanceTakenOn(AStackThatAccepts::THE_ACCEPTANCE))] as $which => $accepting) {
        expect(whatAcceptingCameTo($accepting->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'))))
            ->toBe(sprintf('following %s', AStackThatAccepts::THE_ACCEPTANCE), $which);
    }

    $sent = MockClient::global()->getRecordedResponses()[0]->getPendingRequest();

    expect($sent->getRequest()->resolveEndpoint())->toBe('/api/actions/accept')
        ->and($sent->body()?->all())->toMatchArray(['check' => 'vpn.contained', 'disruptive' => false]);
});

it('reads the report the work came to, the accepted warning still a warning', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOnceAWarningIsAccepted()));

    foreach (['the fake' => AStackThatAccepts::answering(WhatBecameOfTheWork::answered(theSameAcceptedReport())), 'the adapter' => anAcceptorAnswered($finished)] as $which => $accepting) {
        expect(whatAcceptingCameTo($accepting->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named(AStackThatAccepts::THE_ACCEPTANCE))))
            ->toBe('vpn.contained=Accepted', $which);
    }
});

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'accept', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatAcceptingCameTo(anAcceptorAnswered($running)->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatAcceptingCameTo(anAcceptorAnswered($forgotten)->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(aWarningTheStackIsNotRaising()), 422);
    $said = 'refused: Nothing in this run warns about vpn.contained / Answer one of the warnings this run raised';

    expect(whatAcceptingCameTo(anAcceptorAnswered($refused)->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'))))->toBe($said)
        ->and(whatAcceptingCameTo(anAcceptorAnswered($refused)->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatAcceptingCameTo(anAcceptorAnswered($answered)->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'))))->toBe($kind->name)
        ->and(whatAcceptingCameTo(anAcceptorAnswered($answered)->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatAcceptingCameTo(AStackThatAccepts::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of($kind)))->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'))))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads a report it cannot read as an answer it could not read', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOnceAWarningIsAccepted('forgotten')));

    expect(whatAcceptingCameTo(anAcceptorAnswered($finished)->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('reads a handle it cannot follow as an answer it could not read', function (): void {
    expect(whatAcceptingCameTo(anAcceptorAnswered(anAcceptanceTakenOn(' '))->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('the fake remembers each check accepted and each handle followed', function (): void {
    $accepting = AStackThatAccepts::answering(WhatBecameOfTheWork::ended());
    $accepting->accept(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Check::of('vpn.contained'));
    $accepting->whatBecameOf(aStackWhoseWarningIsAccepted(), Session::of('a-session-not-a-secret'), Job::named(AStackThatAccepts::THE_ACCEPTANCE));

    expect($accepting->accepted())->toBe(['vpn.contained'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $accepting->followed()))->toBe([AStackThatAccepts::THE_ACCEPTANCE]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('DoctorEnvelope', whatAStackSaysOnceAWarningIsAccepted()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aWarningTheStackIsNotRaising()))
        ->toBe([], "The refusal this suite stands in for a stack with is not one a stack would send.\n");
});
