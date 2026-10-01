<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALineOfADiff;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Resetters;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatResets;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatAResetSays;
use Tests\Support\WhatTheContractAccepts;

// The ResettingTheConfiguration contract, run against the adapter and against the fake.
//
// Three questions of one port: what putting the configuration back would
// revert, the yes, and what became of either. Both are work the stack names,
// and both finish as the `reset` envelope, told apart by its `confirmed`.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose configuration is put back. */
function aStackWhoseConfigurationGoesBack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** What a stack answers work it took on with. */
function aResetTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'reset', 'job' => $job]]), 202);
}

/**
 * Both ways of putting the configuration back, each set up to say the same.
 *
 * @return array<string, Closure(): ResettingTheConfiguration>
 */
function everyWayOfResetting(MockResponse $answered, HowTheResetIsGoing $preview, HowTheResetIsGoing $after): array
{
    return [
        'the fake' => static fn(): ResettingTheConfiguration => AStackThatResets::previewing($preview, $after),
        'the adapter' => static function () use ($answered): ResettingTheConfiguration {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Resetters(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** A report, every part of it folded to one line. */
function whatTheResetReportSays(TheReset $reset): string
{
    $files = [];

    foreach ($reset->edits() as $edit) {
        $lines = array_map(
            static fn(ALineOfADiff $line): string => sprintf('%s%s', $line->isTheirs() ? '-' : '+', $line->text()),
            iterator_to_array($edit, preserve_keys: false),
        );
        $files[] = sprintf('%s[%s]', $edit->path(), implode(',', $lines));
    }

    return sprintf(
        '%s|%s|%s',
        $reset->wasCarriedOut() ? 'carried out' : 'previewed',
        implode(' ', $files),
        implode(',', iterator_to_array($reset->connections(), preserve_keys: false)),
    );
}

/** What asking for the preview came to, as a line. */
function howThePreviewWasAskedFor(ResettingTheConfiguration $resetting): string
{
    return $resetting->wouldRevert(aStackWhoseConfigurationGoesBack(), Session::of('a-session-not-a-secret'))->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What the yes came to, as a line. */
function howTheResetWasAgreed(ResettingTheConfiguration $resetting): string
{
    return $resetting->revert(aStackWhoseConfigurationGoesBack(), Session::of('a-session-not-a-secret'), AResetAgreed::to(WhatAResetSays::previewed()))->either(
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking after a handle came to, every part of a report folded to one line. */
function whatBecameOfResetting(ResettingTheConfiguration $resetting, string $job = AStackThatResets::THE_PREVIEW): string
{
    return $resetting->whatBecameOf(aStackWhoseConfigurationGoesBack(), Session::of('a-session-not-a-secret'), Job::named($job))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        done: static fn(TheReset $reset): TheWordCarriedOut => new TheWordCarriedOut(whatTheResetReportSays($reset)),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s (%s)', $why->summary(), $why->named()->forTheOperator())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('comes away from asking for a preview with the job the stack named', function (): void {
    foreach (everyWayOfResetting(aResetTakenOn(AStackThatResets::THE_PREVIEW), HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::stillRunning()) as $which => $build) {
        expect(howThePreviewWasAskedFor($build()))->toBe(sprintf('following %s', AStackThatResets::THE_PREVIEW), $which);
    }
});

it('comes away from a preview the stack would not take on with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"no"}', 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('{"error":"busy"}', 409), Obstacle::of(KindOfObstacle::StackIsBusy)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [aResetTakenOn(' '), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howThePreviewWasAskedFor(new Resetters(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name)
            ->and(howThePreviewWasAskedFor(AStackThatResets::met($why)))->toBe($why->kind()->name);
    }
});

it('comes away from a yes with the job the stack named', function (): void {
    foreach (everyWayOfResetting(aResetTakenOn(AStackThatResets::THE_RESET), HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::stillRunning()) as $which => $build) {
        expect(howTheResetWasAgreed($build()))->toBe(sprintf('following %s', AStackThatResets::THE_RESET), $which);
    }
});

it('comes away from a refused yes with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"busy"}', 409), Obstacle::of(KindOfObstacle::StackIsBusy)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [aResetTakenOn(' '), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheResetWasAgreed(new Resetters(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name)
            ->and(howTheResetWasAgreed(AStackThatResets::previewingButRefusing(HowTheResetIsGoing::done(WhatAResetSays::previewed()), $why)))->toBe($why->kind()->name);
    }
});

it('reads a preview: every file with its lines, and every connection, as would-be', function (): void {
    $previewed = MockResponse::make((string) json_encode(WhatAResetSays::envelope(confirmed: false)));

    foreach (everyWayOfResetting($previewed, HowTheResetIsGoing::done(WhatAResetSays::previewed()), HowTheResetIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfResetting($build()))
            ->toBe('previewed|compose.yaml[-image: sonarr:4.0.1,+image: sonarr:4.0.0] env/sonarr.env[]|sonarr → qbittorrent', $which);
    }
});

it('reads a reset carried out as carried out, from the stack\'s own word for it', function (): void {
    $carriedOut = MockResponse::make((string) json_encode(WhatAResetSays::envelope(confirmed: true)));

    foreach (everyWayOfResetting($carriedOut, HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::done(WhatAResetSays::carriedOut())) as $which => $build) {
        expect(whatBecameOfResetting($build(), AStackThatResets::THE_RESET))
            ->toBe('carried out|compose.yaml[-image: sonarr:4.0.1,+image: sonarr:4.0.0] env/sonarr.env[]|sonarr → qbittorrent', $which);
    }
});

it('reads a preview that reverts nothing as exactly that', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(WhatAResetSays::envelope(confirmed: false, changed: ['reverted' => [], 'reverted_connections' => []])))]);

    expect(whatBecameOfResetting(new Resetters(new PinnedClients(), SequencedEntropy::counting())))->toBe('previewed||');
});

it('a report this app cannot read is a stack that did not answer, never a shorter report', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(WhatAResetSays::envelope(confirmed: false, changed: $changed)))]);

    expect(whatBecameOfResetting(new Resetters(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'no word on whether it was carried out' => [['confirmed' => 'yes']],
    'no files' => [['reverted' => null]],
    'files that are not a list' => [['reverted' => ['compose.yaml' => ['path' => 'compose.yaml', 'diff' => '']]]],
    'a file that is not a table' => [['reverted' => ['compose.yaml']]],
    'a file with no path' => [['reverted' => [['diff' => '']]]],
    'a file with a blank path' => [['reverted' => [['path' => ' ', 'diff' => '']]]],
    'a file with no diff' => [['reverted' => [['path' => 'compose.yaml']]]],
    'a diff that is not text' => [['reverted' => [['path' => 'compose.yaml', 'diff' => 3]]]],
    'a line on neither side' => [['reverted' => [['path' => 'compose.yaml', 'diff' => "image: sonarr\n"]]]],
    'no connections' => [['reverted_connections' => 'sonarr']],
    'a connection that is not text' => [['reverted_connections' => [3]]],
    'a connection with no name' => [['reverted_connections' => [' ']]],
]);

it('a report missing a part altogether is a stack that did not answer, never a shorter report', function (string $missing): void {
    $envelope = WhatAResetSays::envelope(confirmed: false);
    $data = $envelope['data'];
    $envelope['data'] = is_array($data) ? array_diff_key($data, [$missing => true]) : [];
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($envelope))]);

    expect(whatBecameOfResetting(new Resetters(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with(['confirmed', 'reverted', 'reverted_connections']);

it('a reset the stack refused is its refusal, in its words, with what it named', function (): void {
    $refused = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'RESET_WRITE',
        'severity' => 'critical',
        'state' => 'guided',
        'summary' => 'A stack file could not be written',
        'meaning' => 'The reset stopped part way.',
        'remedies' => [],
        'detail' => 'compose.yaml',
    ]]), 500);

    foreach (everyWayOfResetting($refused, HowTheResetIsGoing::refused(ARefusalInItsWords::said('A stack file could not be written', 'The reset stopped part way.', WhatTheRefusalNamed::as('compose.yaml'))), HowTheResetIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfResetting($build()))->toBe('refused: A stack file could not be written (compose.yaml)', $which);
    }
});

it('a reset still running is its own answer', function (): void {
    foreach (everyWayOfResetting(aResetTakenOn(AStackThatResets::THE_PREVIEW), HowTheResetIsGoing::stillRunning(), HowTheResetIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfResetting($build()))->toBe('still running', $which);
    }
});

it('a reset the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfResetting($forgotten, HowTheResetIsGoing::ended(), HowTheResetIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfResetting($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'reset', 'job' => AStackThatResets::THE_PREVIEW]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after a reset tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"no"}', 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfResetting($answered, HowTheResetIsGoing::met($why), HowTheResetIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfResetting($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('the fake remembers the previews asked for, the yeses sent and the handles followed', function (): void {
    $resetting = AStackThatResets::previewing(HowTheResetIsGoing::done(WhatAResetSays::previewed()), HowTheResetIsGoing::done(WhatAResetSays::carriedOut()));
    howThePreviewWasAskedFor($resetting);
    whatBecameOfResetting($resetting);
    howTheResetWasAgreed($resetting);

    expect($resetting->previewsAskedFor())->toBe(1)
        ->and($resetting->agreed())->toHaveCount(1)
        ->and(whatBecameOfResetting($resetting, AStackThatResets::THE_RESET))->toStartWith('carried out')
        ->and($resetting->followed())->toBe([AStackThatResets::THE_PREVIEW, AStackThatResets::THE_RESET]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ResetEnvelope', WhatAResetSays::envelope(confirmed: false)))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ResetEnvelope', WhatAResetSays::envelope(confirmed: true)))
        ->toBe([]);
});
