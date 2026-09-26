<?php

declare(strict_types=1);

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Sdk\Api\Bundlers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatBundles;
use Tests\Support\Fakes\SequencedEntropy;
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

/** One line carried out of an `either()` arm. */
final readonly class WhatAskingForABundleSaid
{
    public function __construct(public string $said) {}
}

/** What asking for a bundle came to, as a line. */
function howTheBundleWasAskedFor(AskingForHelp $helping): string
{
    return $helping->ask(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), aBundleAskedFor())->either(
        started: static fn(Job $job): WhatAskingForABundleSaid => new WhatAskingForABundleSaid(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatAskingForABundleSaid => new WhatAskingForABundleSaid($why->name),
    )->said;
}

/** What asking after a bundle came to, every part of it folded to one line. */
function whatBecameOfTheBundle(AskingForHelp $helping): string
{
    return $helping->whatBecameOf(aStackAskedForABundle(), Session::of('a-session-not-a-secret'), Job::named(AStackThatBundles::THE_JOB))->either(
        stillRunning: static fn(): WhatAskingForABundleSaid => new WhatAskingForABundleSaid('still running'),
        done: static fn(ABundle $bundle): WhatAskingForABundleSaid => new WhatAskingForABundleSaid(WhatABundleSays::of($bundle)),
        refused: static fn(string $said): WhatAskingForABundleSaid => new WhatAskingForABundleSaid(sprintf('refused: %s', $said)),
        ended: static fn(): WhatAskingForABundleSaid => new WhatAskingForABundleSaid('ended'),
        met: static fn(Obstacle $why): WhatAskingForABundleSaid => new WhatAskingForABundleSaid($why->name),
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'support', 'job' => ' ']]), 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheBundleWasAskedFor(new Bundlers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->name)
            ->and(howTheBundleWasAskedFor(AStackThatBundles::met($why)))->toBe($why->name);
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

it('a bundle the stack refused is its refusal, in its words', function (): void {
    $refused = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'BUNDLE_LEAK',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => WhatABundleSays::A_LEAK,
        'meaning' => 'Nothing has been written.',
        'remedies' => [],
    ]]), 500);

    foreach (everyWayOfAskingForABundle($refused, HowTheBundleIsGoing::refused(WhatABundleSays::A_LEAK)) as $which => $build) {
        expect(whatBecameOfTheBundle($build()))->toBe(sprintf('refused: %s', WhatABundleSays::A_LEAK), $which);
    }
});

it('asking after a bundle tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForABundle($answered, HowTheBundleIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfTheBundle($build()))->toBe($why->name, $which);
        }
    }
});

it('a bundle this app cannot read is a stack that did not answer, never a shorter bundle', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(WhatABundleSays::envelope(['bytes' => -1])))]);

    expect(whatBecameOfTheBundle(new Bundlers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
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

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BundleEnvelope', WhatABundleSays::envelope()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('JobEnvelope', aBundleTakenOnSays()))
        ->toBe([]);
});
