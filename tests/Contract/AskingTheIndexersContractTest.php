<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AskingTheIndexers;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Searchers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSearches;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\TracesToFollow;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWhoseIndexersAreAsked(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function aSearcherAnswered(MockResponse ...$answered): AskingTheIndexers
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Searchers(new PinnedClients(), SequencedEntropy::counting());
}

/**
 * The handle a stack answers a search with, still running where a case says.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfASearchTakenOn(string $job, bool $running = false): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'search', 'job' => $job, ...($running ? ['state' => 'running'] : [])]];
}

function aSearchTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackSaysOfASearchTakenOn($job)), 202);
}

/**
 * @param WhatBecameOfTheWork<WhereItGotTo> $became
 */
function whatSearchingCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(WhereItGotTo $trace): TheWordCarriedOut => new TheWordCarriedOut(sprintf('traced %s', $trace->item())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('comes away from a search with the job the stack named, sending the term and season the trace was read with, allowed to spend', function (WhatToFollow $following, array $body): void {
    foreach (['the fake' => AStackThatSearches::answering(WhatBecameOfTheWork::ended()), 'the adapter' => aSearcherAnswered(aSearchTakenOn(AStackThatSearches::THE_SEARCH))] as $which => $searching) {
        expect(whatSearchingCameTo($searching->search(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), $following)))
            ->toBe(sprintf('following %s', AStackThatSearches::THE_SEARCH), $which);
    }

    $sent = MockClient::global()->getRecordedResponses()[0]->getPendingRequest();

    expect($sent->getRequest()->resolveEndpoint())->toBe('/api/actions/search')
        ->and($sent->body()?->all())->toBe($body);
})->with([
    'every season' => [WhatToFollow::called('Severance'), ['disruptive' => true, 'term' => 'Severance', 'season' => null]],
    'one season' => [WhatToFollow::calledInSeason('Severance', 2), ['disruptive' => true, 'term' => 'Severance', 'season' => 2]],
]);

it('reads the trace the search came back with', function (): void {
    $finished = MockResponse::make((string) json_encode(TracesToFollow::whatAStackSaysOfTheSeries()));

    foreach (['the fake' => AStackThatSearches::answering(WhatBecameOfTheWork::answered(TracesToFollow::aSeriesStuckDownloading())), 'the adapter' => aSearcherAnswered($finished)] as $which => $searching) {
        expect(whatSearchingCameTo($searching->whatBecameOf(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSearches::THE_SEARCH))))
            ->toBe('traced Severance', $which);
    }
});

it('says a search still going is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(whatAStackSaysOfASearchTakenOn('a-job', running: true)), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatSearchingCameTo(aSearcherAnswered($running)->whatBecameOf(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatSearchingCameTo(aSearcherAnswered($forgotten)->whatBecameOf(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatSearchingCameTo(aSearcherAnswered($answered)->search(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), WhatToFollow::called('Severance'))))->toBe($kind->name)
        ->and(whatSearchingCameTo(aSearcherAnswered($answered)->whatBecameOf(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatSearchingCameTo(AStackThatSearches::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of($kind)))->search(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), WhatToFollow::called('Severance'))))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('the fake remembers each item searched for, with its season, and each handle followed', function (): void {
    $searching = AStackThatSearches::answering(WhatBecameOfTheWork::ended());
    $searching->search(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), WhatToFollow::calledInSeason('Severance', 2));
    $searching->search(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), WhatToFollow::called('Dune'));
    $searching->whatBecameOf(aStackWhoseIndexersAreAsked(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSearches::THE_SEARCH));

    expect($searching->searched())->toBe(['Severance, season 2', 'Dune'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $searching->followed()))->toBe([AStackThatSearches::THE_SEARCH]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', whatAStackSaysOfASearchTakenOn('a-job')))
        ->toBe([], "The handle this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('TraceEnvelope', TracesToFollow::whatAStackSaysOfTheSeries()))
        ->toBe([]);
});
