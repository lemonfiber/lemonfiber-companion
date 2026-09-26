<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingCopies;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Copiers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatTakesCopies;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatAScopeSays;
use Tests\Support\WhatTheContractAccepts;

// The TakingCopies contract, run against the adapter and against the fake.
//
// `KeepingCurrentContractTest`'s shape for the same kind of act: a take that
// answers a handle, and a following that answers the stack's report.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a copy is asked of. */
function aStackThatTakesACopy(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * The payload a stack reports a finished copy with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackReportsOfACopy(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'backup', 'data' => [
        'scope' => ['scope' => 'whole_stack'],
        'path' => '/srv/lemonfiber/backups/lemonfiber-20260925-0300-full.tar.zst',
        'pruned' => ['lemonfiber-20260901-0300-full', 'lemonfiber-20260902-0300-full'],
        'pace' => ['moved' => 1_200_000_000, 'budget' => 629_145_600, 'brisk' => false],
        'rehearsed' => false,
        'sensitive' => true,
        ...$changed,
    ]];
}

/** The report that payload stands for, as the fake is handed it. */
function theSameCopyTaken(): ACopyTaken
{
    return ACopyTaken::reported(
        ScopeOfACopy::theWholeStack(),
        TheCopies::named('lemonfiber-20260901-0300-full', 'lemonfiber-20260902-0300-full'),
        HowACopyPaced::measured(moved: 1_200_000_000, budget: 629_145_600, brisk: false),
        WhetherItHoldsASecret::Secret,
        WhetherItWasRehearsed::CarriedOut,
    );
}

/**
 * Both ways of taking a copy, each set up to say the same.
 *
 * @return array<string, Closure(): TakingCopies>
 */
function everyWayOfTakingACopy(MockResponse $answered, HowTheCopyIsGoing $became): array
{
    return [
        'the fake' => static fn(): TakingCopies => AStackThatTakesCopies::whichTook($became),
        'the adapter' => static function () use ($answered): TakingCopies {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Copiers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTakingACopySaid
{
    public function __construct(public string $said) {}
}

/** What asking for a copy came to, as a line. */
function howTheCopyWasAskedFor(TakingCopies $copying, ACopyAsked $asked): string
{
    return $copying->take(aStackThatTakesACopy(), Session::of('a-session-not-a-secret'), $asked)->either(
        started: static fn(Job $job): WhatTakingACopySaid => new WhatTakingACopySaid(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatTakingACopySaid => new WhatTakingACopySaid($why->name),
    )->said;
}

/** What asking after a copy came to, every part of a report folded to one line. */
function whatBecameOfTheCopy(TakingCopies $copying): string
{
    return $copying->whatBecameOf(aStackThatTakesACopy(), Session::of('a-session-not-a-secret'), Job::named(AStackThatTakesCopies::THE_JOB))->either(
        stillRunning: static fn(): WhatTakingACopySaid => new WhatTakingACopySaid('still running'),
        done: static fn(ACopyTaken $report): WhatTakingACopySaid => new WhatTakingACopySaid(sprintf(
            '%s|pruned %s|%d of %d %s|%s|%s',
            WhatAScopeSays::of($report->scope()),
            implode(',', iterator_to_array($report->pruned(), preserve_keys: false)),
            $report->pace()->moved(),
            $report->pace()->budget(),
            $report->pace()->isBrisk() ? 'brisk' : 'past it',
            $report->holds()->value,
            $report->was()->value,
        )),
        ended: static fn(): WhatTakingACopySaid => new WhatTakingACopySaid('ended'),
        met: static fn(Obstacle $why): WhatTakingACopySaid => new WhatTakingACopySaid($why->name),
    )->said;
}

/** What a stack answers a copy it took on with. */
function aCopyTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'backup', 'job' => AStackThatTakesCopies::THE_JOB]]), 202);
}

it('comes away from asking for a copy with the job the stack named, whatever the scope', function (): void {
    foreach ([ACopyAsked::ofTheWholeStack(), ACopyAsked::ofOneService(ServiceId::called('sonarr'))] as $asked) {
        foreach (everyWayOfTakingACopy(aCopyTakenOn(), HowTheCopyIsGoing::stillRunning()) as $which => $build) {
            expect(howTheCopyWasAskedFor($build(), $asked))->toBe(sprintf('following %s', AStackThatTakesCopies::THE_JOB), $which);
        }
    }
});

it('comes away from a copy that was not taken with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'backup', 'job' => ' ']]), 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheCopyWasAskedFor(new Copiers(new PinnedClients(), SequencedEntropy::counting()), ACopyAsked::ofTheWholeStack()))->toBe($why->name);
        expect(howTheCopyWasAskedFor(AStackThatTakesCopies::met($why), ACopyAsked::ofTheWholeStack()))->toBe($why->name);
    }
});

it('reads every part of a finished copy\'s report, the copies it removed among them', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackReportsOfACopy()));

    foreach (everyWayOfTakingACopy($finished, HowTheCopyIsGoing::done(theSameCopyTaken())) as $which => $build) {
        expect(whatBecameOfTheCopy($build()))
            ->toBe('whole|pruned lemonfiber-20260901-0300-full,lemonfiber-20260902-0300-full|1200000000 of 629145600 past it|secret|carried_out', $which);
    }
});

it('a copy still being taken is its own answer', function (): void {
    foreach (everyWayOfTakingACopy(aCopyTakenOn(), HowTheCopyIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfTheCopy($build()))->toBe('still running', $which);
    }
});

it('a copy the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfTakingACopy($forgotten, HowTheCopyIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfTheCopy($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'backup', 'job' => AStackThatTakesCopies::THE_JOB]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after a copy tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfTakingACopy($answered, HowTheCopyIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfTheCopy($build()))->toBe($why->name, $which);
        }
    }
});

it('a report this app cannot read is a stack that did not answer, never a shorter report', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackReportsOfACopy($changed)))]);

    expect(whatBecameOfTheCopy(new Copiers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'no scope' => [['scope' => null]],
    'a scope with no word this app knows' => [['scope' => ['scope' => 'everything']]],
    'a service with a blank name' => [['scope' => ['scope' => 'service', 'name' => '  ']]],
    'an existing setup with a blank tree' => [['scope' => ['scope' => 'existing', 'project' => 'media', 'trees' => [['host_path' => ' ', 'archive_path' => 'existing/0']]]]],
    'a removed copy with a blank name' => [['pruned' => ['lemonfiber-20260901-0300-full', ' ']]],
    'a size below nothing' => [['pace' => ['moved' => -1, 'budget' => 629_145_600, 'brisk' => true]]],
    'no pace' => [['pace' => 'brisk']],
    'no word on whether it was rehearsed' => [['rehearsed' => 'no']],
]);

it('asking after a copy names the handle asking for it answered', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    whatBecameOfTheCopy($copying);

    expect($copying->followed())->toHaveCount(1)
        ->and($copying->followed()[0]->shown())->toBe(AStackThatTakesCopies::THE_JOB);
});

it('the fake remembers each copy asked for, in order', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    howTheCopyWasAskedFor($copying, ACopyAsked::ofTheWholeStack());
    howTheCopyWasAskedFor($copying, ACopyAsked::ofOneService(ServiceId::called('sonarr')));

    expect(array_map(static fn(ACopyAsked $asked): string => WhatAScopeSays::of($asked->scope()), $copying->taken()))
        ->toBe(['whole', 'service:sonarr']);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BackupEnvelope', whatAStackReportsOfACopy()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('JobEnvelope', ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'backup', 'job' => AStackThatTakesCopies::THE_JOB]]))
        ->toBe([]);
});
