<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
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
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Reversers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatPutsRunsBack;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
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
        started: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking after it came to, every part of a report folded to one line. */
function whatBecameOfPuttingARunBack(PuttingARunBack $puttingBack): string
{
    return $puttingBack->whatBecameOf(aStackARunIsPutBackOn(), Session::of('a-session-not-a-secret'), Job::named(AStackThatPutsRunsBack::THE_JOB))->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('still running'),
        done: static function (ARunPutBack $report): TheWordCarriedOut {
            $reversed = [];

            foreach ($report->reversed() as $change) {
                $reversed[] = sprintf('%s %s', $change->does()->value, $change->target());
            }

            return new TheWordCarriedOut(sprintf(
                '%s|%s|left %s|noted %s',
                $report->rehearsed()->value,
                implode(', ', $reversed),
                changesAndWhySaid($report->left()),
                changesAndWhySaid($report->noted()),
            ));
        },
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            'refused: %s | %s | %s',
            $why->summary(),
            $why->meaning(),
            $why->named()->forTheOperator(),
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * The problem a stack stops putting a run back on, as the error envelope it answers asking after it with.
 *
 * @return array<string, mixed>
 */
function aProblemThatStoppedTheRun(string $code, string $summary, string $meaning, ?string $detail = null): array
{
    $problem = [
        'code' => $code,
        'severity' => 'error',
        'state' => 'actionable',
        'summary' => $summary,
        'meaning' => $meaning,
        'remedies' => [['action' => 'Deal with that change first, or restore from a backup']],
    ];

    return ['api_version' => 1, 'kind' => 'error', 'data' => $detail === null ? $problem : [...$problem, 'detail' => $detail]];
}

/**
 * Every problem the stack documents stopping `undo` on, each with the line it folds to.
 *
 * The four the undo command raises before it touches anything, and a change
 * that could not be reversed part of the way through, which names the file.
 *
 * @return array<string, array{array<string, mixed>, ARefusalInItsWords, string}>
 */
function everyProblemARunStopsOn(): array
{
    $table = [
        'no run under the stamp' => ['UNDO-1', 'Nothing was changed at 1790150000', 'No run in the record carries the stamp 1790150000. It may have fallen outside the horizon the record keeps, or the stamp may be mistyped. Nothing was put back.', null],
        'more than one run under the stamp' => ['UNDO-2', 'More than one run is stamped 1790150000', '1790150000 names add and pin, and putting back the wrong one is not something to guess at. Nothing was put back.', null],
        'a change that cannot be reversed' => ['UNDO-3', 'The run stamped 1790150000 cannot be put back', 'One of its changes, against sonarr, cannot be reversed: the service was removed since. A run goes back whole or not at all, so nothing was put back.', null],
        'nowhere to look' => ['UNDO-4', 'This run has nowhere it knows to look for what was changed', 'What lemonfiber changed is recorded in its own directory, and this machine would not say where that is. Nothing was put back.', null],
        'a region that could not be taken out' => ['SETUP-12', "A region lemonfiber wrote into one of the stack's files could not be taken out", 'Everything before it was put back; this region is still in the file.', '/srv/stack/compose.yaml: permission denied'],
    ];
    $cases = [];

    foreach ($table as $name => [$code, $summary, $meaning, $detail]) {
        $cases[$name] = [
            aProblemThatStoppedTheRun($code, $summary, $meaning, $detail),
            ARefusalInItsWords::said($summary, $meaning, $detail === null ? WhatTheRefusalNamed::nothing() : WhatTheRefusalNamed::as($detail)),
            sprintf('refused: %s | %s | %s', $summary, $meaning, $detail ?? ''),
        ];
    }

    return $cases;
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"no such run"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'undo', 'job' => ' ']]), 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howPuttingARunBackWasAgreed(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
        expect(howPuttingARunBackWasAgreed(AStackThatPutsRunsBack::met($why)))->toBe($why->kind()->name);
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

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
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
    'a reversal doing something this app has no word for' => [['reversed' => [['target' => 'sonarr', 'action' => ['does' => 'teleport']]]]],
]);

it('a report leaving out a list, or whether it was a rehearsal, is a stack that did not answer', function (string $field): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack(without: [$field])))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with(['reversed', 'left', 'rehearsed']);

it('a reversal that leaves out what it does is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfPuttingARunBack(['reversed' => [['target' => 'sonarr']]])))]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
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
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"cannot succeed"}', 422), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfPuttingARunBack($answered, HowPuttingARunBackIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfPuttingARunBack($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('a run the stack would not put back is its refusal, in its words, with what it meant and named', function (): void {
    foreach (everyProblemARunStopsOn() as $case => [$body, $why, $said]) {
        foreach (everyWayOfPuttingARunBack(MockResponse::make((string) json_encode($body), 500), HowPuttingARunBackIsGoing::refused($why)) as $which => $build) {
            expect(whatBecameOfPuttingARunBack($build()))->toBe($said, sprintf('%s, %s', $case, $which));
        }
    }
});

it('a problem at a status that says who may ask is still what was met', function (): void {
    $body = (string) json_encode(aProblemThatStoppedTheRun('UNDO-1', 'Nothing was changed at 1790150000', 'Nothing was put back.'));
    $table = [
        [MockResponse::make($body, 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make($body, 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
    }
});

it('an answer holding no problem the stack wrote is a stack that did not answer, not a refusal', function (MockResponse $answered): void {
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    expect(whatBecameOfPuttingARunBack(new Reversers(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'a sentence with no problem around it' => [MockResponse::make('This machine would not supply the randomness a job needs to be named.', 500, ['Content-Type' => 'text/plain'])],
    'a problem with a blank summary' => [MockResponse::make((string) json_encode(aProblemThatStoppedTheRun('UNDO-1', ' ', 'Nothing was put back.')), 500)],
    'a problem missing its remedies' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'UNDO-1', 'severity' => 'error', 'state' => 'actionable', 'summary' => 'Nothing was changed at 1790150000', 'meaning' => 'Nothing was put back.']]), 500)],
]);

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

    foreach (everyProblemARunStopsOn() as $case => [$body]) {
        expect(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', $body))->toBe([], $case);
    }
});
