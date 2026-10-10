<?php

declare(strict_types=1);

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Sdk\Api\Bundlers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatBundles;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatABundleSays;
use Tests\Support\WhatTheContractAccepts;

// The AskingForHelp contract, run against the adapter and against the fake.
//
// `TakingCopiesContractTest`'s shape for a bundle: asking answers a handle,
// and following it answers the bundle, a refusal in the stack's words, or an
// obstacle.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a bundle is asked of. */
function aStackAskedForABundle(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** A bundle described, with nothing changed from what a bundle is unless somebody asks. */
function aBundleAskedFor(): ABundleAsked
{
    return ABundleAsked::described(HowManyLines::asMuchAsAPhoneShows(), WhatFilenamesShow::Replaced, SettingsToReveal::none());
}

/**
 * Both ways of asking for a bundle, each set up to say the same.
 *
 * @return array<string, Closure(): AskingForHelp>
 */
function everyWayOfAskingForABundle(MockResponse $answered, HowTheBundleIsGoing $became): array
{
    return [
        'the fake' => static fn(): AskingForHelp => AStackThatBundles::whichGathered($became),
        'the adapter' => static function () use ($answered): AskingForHelp {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Bundlers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** What asking for a bundle came to, as a line. */
function howTheBundleWasAskedFor(AskingForHelp $helping): string
{
    return $helping->ask(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), aBundleAskedFor())->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking after a bundle came to, every part of it folded to one line. */
function whatBecameOfTheBundle(AskingForHelp $helping): string
{
    return $helping->whatBecameOf(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), Job::named(AStackThatBundles::THE_JOB))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        done: static fn(ABundle $bundle): TheWordCarriedOut => new TheWordCarriedOut(WhatABundleSays::of($bundle)),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s (%s)', $why->summary(), $why->named()->forTheOperator())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * What a stack answers a bundle it took on with.
 *
 * @return array<string, mixed>
 */
function aBundleTakenOnSays(): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'support', 'job' => AStackThatBundles::THE_JOB]];
}

it('comes away from asking for a bundle with the job the stack named', function (): void {
    foreach (everyWayOfAskingForABundle(MockResponse::make((string) json_encode(aBundleTakenOnSays()), 202), HowTheBundleIsGoing::stillRunning()) as $which => $build) {
        expect(howTheBundleWasAskedFor($build()))->toBe(sprintf('following %s', AStackThatBundles::THE_JOB), $which);
    }
});

it('comes away from a bundle that was not taken on with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'support', 'job' => ' ']]), 202), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheBundleWasAskedFor(new Bundlers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name)
            ->and(howTheBundleWasAskedFor(AStackThatBundles::met($why)))->toBe($why->kind()->name);
    }
});

it('reads every part of a finished bundle, every file whole', function (): void {
    foreach (everyWayOfAskingForABundle(MockResponse::make((string) json_encode(WhatABundleSays::envelope())), HowTheBundleIsGoing::done(WhatABundleSays::described())) as $which => $build) {
        expect(whatBecameOfTheBundle($build()))->toBe(WhatABundleSays::of(WhatABundleSays::described()), $which);
    }
});

it('a bundle still being gathered is its own answer', function (): void {
    foreach (everyWayOfAskingForABundle(MockResponse::make((string) json_encode(aBundleTakenOnSays()), 202), HowTheBundleIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfTheBundle($build()))->toBe('still running', $which);
    }
});

it('a bundle the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfAskingForABundle($forgotten, HowTheBundleIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfTheBundle($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(aBundleTakenOnSays()))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('a bundle the stack refused is its refusal, in its words, with what it named', function (): void {
    $refused = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'BUNDLE_LEAK',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => WhatABundleSays::A_LEAK,
        'meaning' => 'Nothing has been written.',
        'remedies' => [],
        'detail' => WhatABundleSays::A_LEAK_NAMES,
    ]]), 500);

    foreach (everyWayOfAskingForABundle($refused, HowTheBundleIsGoing::refused(ARefusalInItsWords::said(WhatABundleSays::A_LEAK, 'Nothing has been written.', WhatTheRefusalNamed::as(WhatABundleSays::A_LEAK_NAMES)))) as $which => $build) {
        expect(whatBecameOfTheBundle($build()))->toBe(sprintf('refused: %s (%s)', WhatABundleSays::A_LEAK, WhatABundleSays::A_LEAK_NAMES), $which);
    }
});

it('asking after a bundle tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForABundle($answered, HowTheBundleIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfTheBundle($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('a bundle this app cannot read is an answer it could not read, never a shorter bundle', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(WhatABundleSays::envelope(['bytes' => -1])))]);

    expect(whatBecameOfTheBundle(new Bundlers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('asking after a bundle names the handle asking for it answered', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    whatBecameOfTheBundle($helping);

    expect($helping->followed())->toHaveCount(1)
        ->and($helping->followed()[0]->shown())->toBe(AStackThatBundles::THE_JOB);
});

it('the fake remembers each bundle asked for, in order', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::stillRunning());
    howTheBundleWasAskedFor($helping);
    $helping->ask(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), aBundleAskedFor()->written());

    expect(array_map(static fn(ABundleAsked $asked): bool => $asked->writes(), $helping->asked()))->toBe([false, true]);
});

/** What fetching the written bundle's file came to, as a line. */
function whatFetchingTheBundleCameTo(AskingForHelp $helping): string
{
    return $helping->fetch(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), AWrittenBundle::at(WhatABundleSays::WOULD_GO))->either(
        fetched: static fn(ABundleFile $file): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s: %s', $file->named(), $file->bytes())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('fetches a written bundle whole, named for itself and unopened', function (): void {
    foreach (everyWayOfAskingForABundle(MockResponse::make(AStackThatBundles::THE_BYTES), HowTheBundleIsGoing::done(WhatABundleSays::written())) as $which => $build) {
        expect(whatFetchingTheBundleCameTo($build()))
            ->toBe(sprintf('lemonfiber-support-2026-09-26T10-00-00Z.tar.gz: %s', AStackThatBundles::THE_BYTES), $which);
    }
});

it('comes away from a bundle it could not fetch with the obstacle rather than a file', function (MockResponse $answered, Obstacle $why): void {
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    expect(whatFetchingTheBundleCameTo(new Bundlers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name)
        ->and(whatFetchingTheBundleCameTo(AStackThatBundles::whichGatheredAndCouldNotServe(HowTheBundleIsGoing::ended(), $why)))->toBe($why->kind()->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
    'an account that may not ask' => [MockResponse::make('{"error":"no"}', 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
    'a bundle no longer there' => [MockResponse::make('{"error":"gone"}', 404), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
]);

it('the fake remembers each written bundle it was asked for the file of', function (): void {
    $helping = AStackThatBundles::whichGathered(HowTheBundleIsGoing::done(WhatABundleSays::written()));
    whatFetchingTheBundleCameTo($helping);

    expect(array_map(static fn(AWrittenBundle $written): string => $written->name(), $helping->fetched()))
        ->toBe(['lemonfiber-support-2026-09-26T10-00-00Z.tar.gz']);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BundleEnvelope', WhatABundleSays::envelope()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('JobEnvelope', aBundleTakenOnSays()))
        ->toBe([]);
});
