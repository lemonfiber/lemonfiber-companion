<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARequestLimit;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheHouseholdAsks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheAllowanceChange;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\WhoAnAllowanceIsFor;
use Modules\Sdk\Api\Allowers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatChoosesWhatIsAllowed;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWhoseHouseholdIsAllowed(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a change to what the household may ask for with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfAnAllowanceChange(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'household', 'data' => [
        'available' => true,
        'rehearsed' => true,
        'findings' => ['the household waits for you, and is counted against 5 requests every 7 days — rehearsed, and nothing was written'],
        'members' => [],
        ...$changed,
    ]];
}

function theSameRehearsalOfTheAllowance(): TheAllowanceAsChanged
{
    return TheAllowanceAsChanged::said(
        WhetherItWasRehearsed::Rehearsed,
        Sentences::of(Sentence::of('the household waits for you, and is counted against 5 requests every 7 days — rehearsed, and nothing was written')),
    );
}

/** @return array<string, mixed> */
function aMemberTheStackCannotFind(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'ASK-4',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'nobody called sam is in the household',
        'meaning' => 'A limit set on the wrong person holds back somebody who asked for nothing.',
        'remedies' => [['action' => 'Choose one of: alex, robin']],
    ]];
}

function anAllowerAnswered(MockResponse ...$answered): ChoosingWhatTheHouseholdMayAsk
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Allowers(new PinnedClients(), SequencedEntropy::counting());
}

function anAllowanceChangeTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'household-allow', 'job' => $job]]), 202);
}

/** @return array<string, Closure(): ChoosingWhatTheHouseholdMayAsk> */
function everyWayOfChoosingWhatIsAllowed(MockResponse $answered, WhatBecameOfTheAllowanceChange $became): array
{
    return [
        'the fake' => static fn(): ChoosingWhatTheHouseholdMayAsk => AStackThatChoosesWhatIsAllowed::answering($became, $became),
        'the adapter' => static fn(): ChoosingWhatTheHouseholdMayAsk => anAllowerAnswered($answered),
    ];
}

function whatTheAllowanceChangeCameTo(WhatBecameOfTheAllowanceChange $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static function (TheAllowanceAsChanged $report): TheWordCarriedOut {
            $said = [];

            foreach ($report->whatItSays() as $sentence) {
                $said[] = $sentence->shown();
            }

            return new TheWordCarriedOut(sprintf('%s|%s', $report->did()->value, implode(' / ', $said)));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

function everythingWaitsForEverybody(): AnAllowanceChange
{
    return AnAllowanceChange::ofPolicy(WhoAnAllowanceIsFor::everybody(), HowTheHouseholdAsks::EverythingWaits);
}

it('comes away from rehearsing with the job the stack named', function (): void {
    foreach (everyWayOfChoosingWhatIsAllowed(anAllowanceChangeTakenOn(AStackThatChoosesWhatIsAllowed::THE_REHEARSAL), WhatBecameOfTheAllowanceChange::ended()) as $which => $build) {
        expect(whatTheAllowanceChangeCameTo($build()->rehearse(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), everythingWaitsForEverybody())))
            ->toBe(sprintf('following %s', AStackThatChoosesWhatIsAllowed::THE_REHEARSAL), $which);
    }
});

it('reads what the work came to, the rehearsal labelled as one and the stack\'s sentences in its order', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAnAllowanceChange()));

    foreach (everyWayOfChoosingWhatIsAllowed($finished, WhatBecameOfTheAllowanceChange::answered(theSameRehearsalOfTheAllowance())) as $which => $build) {
        expect(whatTheAllowanceChangeCameTo($build()->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named(AStackThatChoosesWhatIsAllowed::THE_REHEARSAL))))
            ->toBe('rehearsed|the household waits for you, and is counted against 5 requests every 7 days — rehearsed, and nothing was written', $which);
    }
});

it('reads a change that was made as made', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAnAllowanceChange(['rehearsed' => false, 'findings' => ['alex now waits for you', 'the media server is slow to answer']])));

    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered($finished)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe('carried_out|alex now waits for you / the media server is slow to answer');
});

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'household-allow', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered($running)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatTheAllowanceChangeCameTo(anAllowerAnswered($forgotten)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(aMemberTheStackCannotFind()), 422);
    $said = 'refused: nobody called sam is in the household / Choose one of: alex, robin';
    $forSam = AnAllowanceChange::ofLimit(WhoAnAllowanceIsFor::somebody(SomebodyInTheHousehold::called('sam')), ARequestLimit::of(5, 7));

    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered($refused)->rehearse(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $forSam)))->toBe($said)
        ->and(whatTheAllowanceChangeCameTo(anAllowerAnswered($refused)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered($answered)->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), everythingWaitsForEverybody())))->toBe($kind->name)
        ->and(whatTheAllowanceChangeCameTo(anAllowerAnswered($answered)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatTheAllowanceChangeCameTo(AStackThatChoosesWhatIsAllowed::sayingAtOnce(WhatBecameOfTheAllowanceChange::met(Obstacle::of($kind)))->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), everythingWaitsForEverybody())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads work that came back unreadable as an answer it could not read, never a shorter one', function (array $changed): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAnAllowanceChange($changed)));

    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered($finished)->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'a blank finding' => [['findings' => [' ']]],
    'a finding that is not a sentence' => [['findings' => [3]]],
    'no word on whether it was rehearsed' => [['rehearsed' => 'yes']],
]);

it('reads a handle it cannot follow as an answer it could not read', function (): void {
    expect(whatTheAllowanceChangeCameTo(anAllowerAnswered(anAllowanceChangeTakenOn(' '))->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), everythingWaitsForEverybody())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

/** @return array<mixed> */
function whatWasSentToChooseWhatIsAllowed(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        ...(is_array($body) ? $body : []),
    ];
}

it('sends only what was named, the rehearsal as a dry run and the yes without one', function (): void {
    $allowers = anAllowerAnswered(anAllowanceChangeTakenOn('a'), anAllowanceChangeTakenOn('b'), anAllowanceChangeTakenOn('c'));
    $both = AnAllowanceChange::ofBoth(WhoAnAllowanceIsFor::somebody(SomebodyInTheHousehold::called('alex')), HowTheHouseholdAsks::WithinALimit, ARequestLimit::of(5, 7));
    $onlyALimit = AnAllowanceChange::ofLimit(WhoAnAllowanceIsFor::everybody(), ARequestLimit::of(3, 30));

    $allowers->rehearse(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $both);
    $allowers->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $both);
    $allowers->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $onlyALimit);

    [$rehearsal, $yes, $limit] = array_map(whatWasSentToChooseWhatIsAllowed(...), MockClient::global()->getRecordedResponses());
    $parts = ['name' => 'alex', 'policy' => 'within-a-limit', 'requests' => 5, 'days' => 7];

    expect($rehearsal)->toMatchArray(['to' => '/api/actions/household-allow', 'dry_run' => true, ...$parts])
        ->and($yes)->toMatchArray(['to' => '/api/actions/household-allow', ...$parts])
        ->and($yes['dry_run'] ?? false)->toBeFalse()
        ->and($limit)->toMatchArray(['requests' => 3, 'days' => 30])
        ->and($limit['name'] ?? null)->toBeNull()
        ->and($limit['policy'] ?? null)->toBeNull();
});

it('the fake remembers each change rehearsed and carried out, and each handle followed', function (): void {
    $choosing = AStackThatChoosesWhatIsAllowed::answering(WhatBecameOfTheAllowanceChange::answered(theSameRehearsalOfTheAllowance()), WhatBecameOfTheAllowanceChange::ended());
    $change = everythingWaitsForEverybody();
    $choosing->rehearse(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $change);
    $choosing->carryOut(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), $change);
    $became = $choosing->whatBecameOf(aStackWhoseHouseholdIsAllowed(), Session::of('a-session-not-a-secret'), Job::named(AStackThatChoosesWhatIsAllowed::THE_CHANGE));

    expect($choosing->rehearsed())->toBe([$change])
        ->and($choosing->carriedOut())->toBe([$change])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $choosing->followed()))->toBe([AStackThatChoosesWhatIsAllowed::THE_CHANGE])
        ->and(whatTheAllowanceChangeCameTo($became))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', whatAStackSaysOfAnAllowanceChange()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aMemberTheStackCannotFind()))
        ->toBe([], "The refusal this suite stands in for a stack with is not one a stack would send.\n");
});
