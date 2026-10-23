<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\RunningACheck;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\CheckRunners;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatRunsChecks;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWhoseCheckIsRun(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function theKillswitchCheck(): ACheckThatDisturbs
{
    return ACheckThatDisturbs::boundedTo('The tunnel goes down while it is tested', 'vpn.killswitch', 30);
}

/**
 * The report a stack answers a run with: the check it ran, passed.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOnceTheCheckRan(): array
{
    return ['api_version' => 1, 'kind' => 'doctor', 'data' => [
        'overall' => 'healthy',
        'rehearsed' => false,
        'findings' => [[
            'check' => 'vpn.killswitch',
            'category' => 'vpn',
            'title' => 'Traffic stops when the tunnel drops',
            'origin' => ['origin' => 'bundled'],
            'verdict' => ['outcome' => 'pass'],
        ]],
    ]];
}

function theSameReportOfTheRun(): Report
{
    return Report::of(Overall::Healthy, Findings::of(Finding::of(
        Check::of('vpn.killswitch'),
        Category::Vpn,
        'Traffic stops when the tunnel drops',
        Conclusion::Passed,
        WhatTheCheckSaid::nothingWrong(),
        WhoPutItThere::bundled(),
    )));
}

function aCheckRunnerAnswered(MockResponse ...$answered): RunningACheck
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new CheckRunners(new PinnedClients(), SequencedEntropy::counting());
}

function aCheckRunTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'diagnose', 'job' => $job]]), 202);
}

/**
 * @param WhatBecameOfTheWork<Report> $became
 */
function whatRunningCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static function (Report $report): TheWordCarriedOut {
            $said = [];

            foreach ($report->findings() as $finding) {
                $said[] = sprintf('%s=%s', $finding->check()->shown(), $finding->conclusion()->name);
            }

            return new TheWordCarriedOut(implode(' ', $said));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('comes away from a run with the job the stack named, sending diagnose narrowed to the check with the checks that disturb included', function (): void {
    foreach (['the fake' => AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended()), 'the adapter' => aCheckRunnerAnswered(aCheckRunTakenOn(AStackThatRunsChecks::THE_RUN))] as $which => $running) {
        expect(whatRunningCameTo($running->run(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), theKillswitchCheck())))
            ->toBe(sprintf('following %s', AStackThatRunsChecks::THE_RUN), $which);
    }

    $sent = MockClient::global()->getRecordedResponses()[0]->getPendingRequest();

    expect($sent->getRequest()->resolveEndpoint())->toBe('/api/actions/diagnose')
        ->and($sent->body()?->all())->toBe(['only' => 'vpn.killswitch', 'disruptive' => true]);
});

it('reads the report the run came to', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOnceTheCheckRan()));

    foreach (['the fake' => AStackThatRunsChecks::answering(WhatBecameOfTheWork::answered(theSameReportOfTheRun())), 'the adapter' => aCheckRunnerAnswered($finished)] as $which => $running) {
        expect(whatRunningCameTo($running->whatBecameOf(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), Job::named(AStackThatRunsChecks::THE_RUN))))
            ->toBe('vpn.killswitch=Passed', $which);
    }
});

it('says a run still going is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'diagnose', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatRunningCameTo(aCheckRunnerAnswered($running)->whatBecameOf(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatRunningCameTo(aCheckRunnerAnswered($forgotten)->whatBecameOf(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatRunningCameTo(aCheckRunnerAnswered($answered)->run(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), theKillswitchCheck())))->toBe($kind->name)
        ->and(whatRunningCameTo(aCheckRunnerAnswered($answered)->whatBecameOf(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatRunningCameTo(AStackThatRunsChecks::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of($kind)))->run(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), theKillswitchCheck())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('the fake remembers each check run and each handle followed', function (): void {
    $running = AStackThatRunsChecks::answering(WhatBecameOfTheWork::ended());
    $running->run(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), theKillswitchCheck());
    $running->whatBecameOf(aStackWhoseCheckIsRun(), Session::of('a-session-not-a-secret'), Job::named(AStackThatRunsChecks::THE_RUN));

    expect($running->ran())->toBe(['vpn.killswitch'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $running->followed()))->toBe([AStackThatRunsChecks::THE_RUN]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('DoctorEnvelope', whatAStackSaysOnceTheCheckRan()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
