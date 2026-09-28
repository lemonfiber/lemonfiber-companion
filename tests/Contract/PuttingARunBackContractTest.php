<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Reversers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatPutsRunsBack;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The PuttingARunBack contract, run against the adapter and against the fake.
//
// Two questions of one port: the yes, and what became of it.
// `PuttingBackContractTest`'s shape for both.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a run is put back on. */
function aStackARunIsPutBackOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The run these cases agree to, as the record shows it. */
function theRunAgreedTo(): ARunAgreedTo
{
    $change = Change::made('Set LIBRARY_PATH', 'reconfigure', 'lemonfiber', WhenItWasMade::at(Instant::atEpochSeconds(1_790_150_000)), HowFarItGoesBack::Whole, 1);

    return ARunAgreedTo::by(TheRecord::reaching('the last ninety days', $change)->theRun(ARun::stamped('1790150000')));
}

/**
 * What a stack answers about putting a run back, changed where a case says,
 * and without the fields a case leaves out altogether.
 *
 * @param  array<mixed>         $changed
 * @param  list<string>         $without
 * @return array<string, mixed>
 */
function whatAStackSaysOfPuttingARunBack(array $changed = [], array $without = []): array
{
    $data = [
        'reversed' => [
            ['target' => 'lemonfiber', 'action' => ['does' => 'restore', 'key' => 'LIBRARY_PATH', 'wrote' => '/srv/new']],
            ['target' => 'sonarr', 'action' => ['does' => 'delete', 'path' => '/srv/sonarr/extra']],
        ],
        'left' => [['target' => 'sonarr', 'because' => 'the service that made it did not answer']],
        'noted' => [['target' => 'lemonfiber', 'because' => 'the library stays where it was moved to']],
        'rehearsed' => false,
        ...$changed,
    ];

    foreach ($without as $field) {
        unset($data[$field]);
    }

    return ['api_version' => 1, 'kind' => 'undo', 'data' => $data];
}

/** The report that payload stands for, as the fake is handed it. */
function theSameRunPutBack(): ARunPutBack
{
    return ARunPutBack::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhatWentBack::these(
            AChangePutBack::against('lemonfiber', WhatGoingBackDoes::Restore),
            AChangePutBack::against('sonarr', WhatGoingBackDoes::Delete),
        ),
        ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'the service that made it did not answer')),
        ChangesAndWhy::these(AChangeAndWhy::said('lemonfiber', 'the library stays where it was moved to')),
    );
}

/**
 * Both ways of putting a run back, each set up to say the same.
 *
 * @return array<string, Closure(): PuttingARunBack>
 */
function everyWayOfPuttingARunBack(MockResponse $answered, HowPuttingARunBackIsGoing $became): array
{
    return [
        'the fake' => static fn(): PuttingARunBack => AStackThatPutsRunsBack::saying($became),
        'the adapter' => static function () use ($answered): PuttingARunBack {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Reversers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatPuttingARunBackSaid
{
    public function __construct(public string $said) {}
}

/** One list of changes and what is said of each, as a line. */
function changesAndWhySaid(ChangesAndWhy $changes): string
{
    $said = [];

    foreach ($changes as $change) {
        $said[] = sprintf('%s: %s', $change->target(), $change->because());
    }

    return implode(', ', $said);
}

/** What the yes came to, as a line. */
function howPuttingARunBackWasAgreed(PuttingARunBack $puttingBack): string
{
    return $puttingBack->putBack(aStackARunIsPutBackOn(), Session::of('a-session-not-a-secret'), theRunAgreedTo())->either(
        started: static fn(Job $job): WhatPuttingARunBackSaid => new WhatPuttingARunBackSaid(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatPuttingARunBackSaid => new WhatPuttingARunBackSaid($why->name),
    )->said;
}

/** What asking after it came to, every part of a report folded to one line. */
function whatBecameOfPuttingARunBack(PuttingARunBack $puttingBack): string
{
    return $puttingBack->whatBecameOf(aStackARunIsPutBackOn(), Session::of('a-session-not-a-secret'), Job::named(AStackThatPutsRunsBack::THE_JOB))->either(
        stillRunning: static fn(): WhatPuttingARunBackSaid => new WhatPuttingARunBackSaid('still running'),
        done: static function (ARunPutBack $report): WhatPuttingARunBackSaid {
            $reversed = [];

            foreach ($report->reversed() as $change) {
                $reversed[] = sprintf('%s %s', $change->does()->value, $change->target());
            }

            return new WhatPuttingARunBackSaid(sprintf(
                '%s|%s|left %s|noted %s',
                $report->rehearsed()->value,
                implode(', ', $reversed),
                changesAndWhySaid($report->left()),
                changesAndWhySaid($report->noted()),
            ));
        },
        ended: static fn(): WhatPuttingARunBackSaid => new WhatPuttingARunBackSaid('ended'),
        met: static fn(Obstacle $why): WhatPuttingARunBackSaid => new WhatPuttingARunBackSaid($why->name),
    )->said;
}

/** What a stack answers a yes it took on with. */
function aRunTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'undo', 'job' => AStackThatPutsRunsBack::THE_JOB]]), 202);
}

it('comes away from a yes with the job the stack named', function (): void {
    foreach (everyWayOfPuttingARunBack(aRunTakenOn(), HowPuttingARunBackIsGoing::stillRunning()) as $which => $build) {
        expect(howPuttingARunBackWasAgreed($build()))->toBe(sprintf('following %s', AStackThatPutsRunsBack::THE_JOB), $which);
    }
});

it('comes away from a refused yes with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"no such run"}', 409), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'undo', 'job' => ' ']]), 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howPuttingARunBackWasAgreed(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->name);
        expect(howPuttingARunBackWasAgreed(AStackThatPutsRunsBack::met($why)))->toBe($why->name);
    }
});

it('reads what went back, what was left and why, and what was noted', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack()));

    foreach (everyWayOfPuttingARunBack($finished, HowPuttingARunBackIsGoing::done(theSameRunPutBack())) as $which => $build) {
        expect(whatBecameOfPuttingARunBack($build()))
            ->toBe('carried_out|restore lemonfiber, delete sonarr|left sonarr: the service that made it did not answer|noted lemonfiber: the library stays where it was moved to', $which);
    }
});

it('reads a rehearsal as one, and a report that left nothing as leaving nothing', function (array $changed, string $said): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack($changed)))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe($said);
})->with([
    'rehearsed' => [['rehearsed' => true, 'left' => [], 'noted' => []], 'rehearsed|restore lemonfiber, delete sonarr|left |noted '],
    'nothing left' => [['left' => []], 'carried_out|restore lemonfiber, delete sonarr|left |noted lemonfiber: the library stays where it was moved to'],
]);

it('reads a report that leaves out what was noted as noting nothing', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack(without: ['noted'])))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))
        ->toBe('carried_out|restore lemonfiber, delete sonarr|left sonarr: the service that made it did not answer|noted ');
});

it('a report this app cannot read is a stack that did not answer, never a shorter report', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack($changed)))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'no left' => [['left' => null]],
    'no reversed' => [['reversed' => 'all of it']],
    'no rehearsal flag' => [['rehearsed' => 'no']],
    'a row left that is not a row' => [['left' => ['sonarr']]],
    'a row left with no reason' => [['left' => [['target' => 'sonarr', 'because' => ' ']]]],
    'a row left with no target' => [['left' => [['because' => 'it did not answer']]]],
    'a row noted that is not a row' => [['noted' => [42]]],
    'noted as nothing at all' => [['noted' => null]],
    'a reversal that is not a row' => [['reversed' => ['lemonfiber']]],
    'a reversal whose target is not text' => [['reversed' => [['target' => 42, 'action' => ['does' => 'delete', 'path' => '/srv']]]]],
    'a reversal with a blank target' => [['reversed' => [['target' => ' ', 'action' => ['does' => 'delete', 'path' => '/srv']]]]],
    'a reversal with no action' => [['reversed' => [['target' => 'sonarr', 'action' => 'delete']]]],
    'a reversal doing nothing it names' => [['reversed' => [['target' => 'sonarr', 'action' => ['path' => '/srv']]]]],
    'a reversal doing something this app has no word for' => [['reversed' => [['target' => 'sonarr', 'action' => ['does' => 'rewind']]]]],
]);

it('a report leaving out a list, or whether it was a rehearsal, is a stack that did not answer', function (string $field): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack(without: [$field])))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
})->with(['reversed', 'left', 'rehearsed']);

it('a reversal that leaves out what it does is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack(['reversed' => [['target' => 'sonarr']]])))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('a run still going back is its own answer', function (): void {
    foreach (everyWayOfPuttingARunBack(aRunTakenOn(), HowPuttingARunBackIsGoing::stillRunning()) as $which => $build) {
        expect(whatBecameOfPuttingARunBack($build()))->toBe('still running', $which);
    }
});

it('a run the stack no longer has a job for is ended, not unreachable and not running', function (MockResponse $forgotten): void {
    foreach (everyWayOfPuttingARunBack($forgotten, HowPuttingARunBackIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfPuttingARunBack($build()))->toBe('ended', $which);
    }
})->with([
    'let go of' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'undo', 'job' => AStackThatPutsRunsBack::THE_JOB]]))],
    'never known' => [MockResponse::make('{"error":"no such job"}', 404)],
]);

it('asking after a run tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"cannot succeed"}', 409), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfPuttingARunBack($answered, HowPuttingARunBackIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfPuttingARunBack($build()))->toBe($why->name, $which);
        }
    }
});

it('the fake remembers the run agreed to and the handle followed', function (): void {
    $puttingBack = AStackThatPutsRunsBack::saying(HowPuttingARunBackIsGoing::stillRunning());
    howPuttingARunBackWasAgreed($puttingBack);
    whatBecameOfPuttingARunBack($puttingBack);

    expect(array_map(static fn(ARunAgreedTo $agreed): string => $agreed->run()->stamp(), $puttingBack->agreed()))->toBe(['1790150000'])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $puttingBack->followed()))->toBe([AStackThatPutsRunsBack::THE_JOB]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UndoEnvelope', whatAStackSaysOfPuttingARunBack()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('UndoEnvelope', whatAStackSaysOfPuttingARunBack(['rehearsed' => true, 'left' => [], 'noted' => []])))
        ->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('UndoEnvelope', whatAStackSaysOfPuttingARunBack(without: ['noted'])))
        ->toBe([]);
});
