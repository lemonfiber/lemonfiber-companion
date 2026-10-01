<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheGuardSaw;
use Modules\Sdk\Api\Guards;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatGuards;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The Guarding contract, run against the adapter and against the fake.
//
// `TakingCopiesContractTest`'s shape for a guard: a start that answers a
// name, a following that answers where the guard stands, and a release by the
// same name that answers the same.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a guard is asked of. */
function aStackThatGuardsItsData(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** A guard for two forms. */
function aGuardForTwoForms(): AGuardAskedFor
{
    return AGuardAskedFor::of(Forms::these(Form::called('media'), Form::called('downloads')));
}

/**
 * The payload a stack reports a guard that saw the data location go with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackReportsOfAGuard(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'watch', 'data' => [
        'forms' => ['media', 'downloads'],
        'stopped' => false,
        'reason' => 'The data root at /srv/media is gone.',
        ...$changed,
    ]];
}

/** What a stack answers a guard it took on, or one that ended, with. */
function aGuardNamed(int $status): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'watch', 'job' => AStackThatGuards::THE_JOB]]), $status);
}

/**
 * Both ways of guarding, each set up to say the same.
 *
 * @return array<string, Closure(): Guarding>
 */
function everyWayOfGuarding(MockResponse $answered, HowTheGuardIsGoing $became): array
{
    return [
        'the fake' => static fn(): Guarding => AStackThatGuards::whichGuarded($became),
        'the adapter' => static function () use ($answered): Guarding {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Guards(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatGuardingSaid
{
    public function __construct(public string $said) {}
}

/** What asking for a guard came to, as a line. */
function howTheGuardWasAskedFor(Guarding $guarding): string
{
    return $guarding->guard(aStackThatGuardsItsData(), Session::of('a-session-not-a-secret'), aGuardForTwoForms())->either(
        started: static fn(Job $job): WhatGuardingSaid => new WhatGuardingSaid(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatGuardingSaid => new WhatGuardingSaid($why->kind()->name),
    )->said;
}

/** Where a guard stands, every part of it folded to one line. */
function whereTheGuardStands(HowTheGuardIsGoing $going): string
{
    return $going->either(
        guarding: static fn(): WhatGuardingSaid => new WhatGuardingSaid('guarding'),
        sawItGo: static fn(WhatTheGuardSaw $saw): WhatGuardingSaid => new WhatGuardingSaid(sprintf(
            'saw it go|%s|%s|%s',
            implode(',', array_map(static fn(Form $form): string => $form->named(), [...$saw->forms()])),
            $saw->stoppedThem() ? 'stopped them' : 'did not stop them',
            $saw->reason(),
        )),
        refused: static fn(string $said): WhatGuardingSaid => new WhatGuardingSaid(sprintf('refused|%s', $said)),
        ended: static fn(): WhatGuardingSaid => new WhatGuardingSaid('ended'),
        unknown: static fn(): WhatGuardingSaid => new WhatGuardingSaid('unknown'),
        met: static fn(Obstacle $why): WhatGuardingSaid => new WhatGuardingSaid($why->kind()->name),
    )->said;
}

/** What asking after a guard came to. */
function whatBecameOfTheGuard(Guarding $guarding): string
{
    return whereTheGuardStands($guarding->whatBecameOf(aStackThatGuardsItsData(), Session::of('a-session-not-a-secret'), Job::named(AStackThatGuards::THE_JOB)));
}

it('comes away from asking for a guard with the job the stack named', function (): void {
    foreach (everyWayOfGuarding(aGuardNamed(202), HowTheGuardIsGoing::stillGuarding()) as $which => $build) {
        expect(howTheGuardWasAskedFor($build()))->toBe(sprintf('following %s', AStackThatGuards::THE_JOB), $which);
    }
});

it('sends the action the guard is asked by, naming every form', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([aGuardNamed(202)]);

    howTheGuardWasAskedFor(new Guards(new PinnedClients(), SequencedEntropy::counting()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/actions/watch')
        ->and($sent?->body()?->all())->toBe(['forms' => ['media', 'downloads']]);
});

it('comes away from a guard that was not taken on with the obstacle rather than a job', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'watch', 'job' => ' ']]), 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);
        expect(howTheGuardWasAskedFor(new Guards(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->kind()->name);
        expect(howTheGuardWasAskedFor(AStackThatGuards::met($why)))->toBe($why->kind()->name);
    }
});

it('a guard still guarding is its own answer', function (): void {
    foreach (everyWayOfGuarding(aGuardNamed(202), HowTheGuardIsGoing::stillGuarding()) as $which => $build) {
        expect(whatBecameOfTheGuard($build()))->toBe('guarding', $which);
    }
});

it('reads every part of a guard that saw the data location go, whether it stopped the forms above all', function (bool $stopped, string $said): void {
    $finished = MockResponse::make((string) json_encode(whatAStackReportsOfAGuard(['stopped' => $stopped])));
    $saw = WhatTheGuardSaw::of(Forms::these(Form::called('media'), Form::called('downloads')), $stopped, 'The data root at /srv/media is gone.');

    foreach (everyWayOfGuarding($finished, HowTheGuardIsGoing::sawItGo($saw)) as $which => $build) {
        expect(whatBecameOfTheGuard($build()))->toBe(sprintf('saw it go|media,downloads|%s|The data root at /srv/media is gone.', $said), $which);
    }
})->with([
    'stopped them' => [true, 'stopped them'],
    'could not stop them' => [false, 'did not stop them'],
]);

/**
 * The error a stack refuses a guard it could not start with.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfAGuardItCouldNotStart(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'LF-C5-001',
        'meaning' => 'There is nothing to guard.',
        'remedies' => [],
        'severity' => 'error',
        'state' => 'actionable',
        'summary' => 'No data location is configured, so there is nothing to guard.',
    ]];
}

it('a guard that never started carries the stack\'s own reason, from an error or from prose', function (MockResponse $refused): void {
    foreach (everyWayOfGuarding($refused, HowTheGuardIsGoing::refused('No data location is configured, so there is nothing to guard.')) as $which => $build) {
        expect(whatBecameOfTheGuard($build()))->toBe('refused|No data location is configured, so there is nothing to guard.', $which);
    }
})->with([
    'an error' => [MockResponse::make((string) json_encode(whatAStackSaysOfAGuardItCouldNotStart()), 409)],
    'prose' => [MockResponse::make('No data location is configured, so there is nothing to guard.', 409)],
]);

it('a guard ended without an outcome is ended, and one the stack no longer knows is unknown', function (): void {
    foreach (everyWayOfGuarding(aGuardNamed(200), HowTheGuardIsGoing::ended()) as $which => $build) {
        expect(whatBecameOfTheGuard($build()))->toBe('ended', $which);
    }

    foreach (everyWayOfGuarding(MockResponse::make('{"error":"No work in this run goes by that name."}', 404), HowTheGuardIsGoing::unknown()) as $which => $build) {
        expect(whatBecameOfTheGuard($build()))->toBe('unknown', $which);
    }
});

it('asking after a guard tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"not yours"}', 403), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfGuarding($answered, HowTheGuardIsGoing::met($why)) as $which => $build) {
            expect(whatBecameOfTheGuard($build()))->toBe($why->kind()->name, sprintf('%s / %s', $which, $why->kind()->name));
        }
    }
});

it('a report this app cannot read is a stack that did not answer, never a guard that stopped something', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackReportsOfAGuard($changed)))]);

    expect(whatBecameOfTheGuard(new Guards(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'no word on whether it stopped them' => [['stopped' => 'yes']],
    'no reason' => [['reason' => ' ']],
    'no forms' => [['forms' => 'media']],
    'forms that are not a list' => [['forms' => ['a' => 'media']]],
    'a form with a blank name' => [['forms' => ['media', ' ']]],
    'a form that is not text' => [['forms' => [7]]],
]);

it('a payload with no data is a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'watch', 'data' => 'gone']))]);

    expect(whatBecameOfTheGuard(new Guards(new PinnedClients(), SequencedEntropy::counting())))->toEqual(KindOfObstacle::StackDidNotAnswer->name);
});

it('lets a guard go by its name, and answers where it then stands', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([aGuardNamed(200)]);

    $released = new Guards(new PinnedClients(), SequencedEntropy::counting())
        ->letGo(aStackThatGuardsItsData(), Session::of('a-session-not-a-secret'), Job::named(AStackThatGuards::THE_JOB));
    $sent = $mock->getLastPendingRequest();

    expect(whereTheGuardStands($released))->toBe('ended')
        ->and($sent?->getMethod()->value)->toBe('DELETE')
        ->and($sent?->getUrl())->toEndWith(sprintf('/api/jobs/%s', AStackThatGuards::THE_JOB));

    $fake = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding());

    expect(whereTheGuardStands($fake->letGo(aStackThatGuardsItsData(), Session::of('a-session-not-a-secret'), Job::named(AStackThatGuards::THE_JOB))))->toBe('ended')
        ->and($fake->letGoOf())->toHaveCount(1);
});

it('letting go of a guard the stack no longer knows is unknown, and a refusal is what the operator met', function (): void {
    foreach ([
        [MockResponse::make('{"error":"No work in this run goes by that name."}', 404), 'unknown'],
        [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused->name],
        [MockResponse::make('not json at all'), KindOfObstacle::StackDidNotAnswer->name],
    ] as [$answered, $said]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);

        expect(whereTheGuardStands(new Guards(new PinnedClients(), SequencedEntropy::counting())
            ->letGo(aStackThatGuardsItsData(), Session::of('a-session-not-a-secret'), Job::named(AStackThatGuards::THE_JOB))))->toBe($said);
    }
});

it('the fake answers each standing it was given in turn, then stays on the last, and remembers what it was asked', function (): void {
    $guarding = AStackThatGuards::whichGuarded(HowTheGuardIsGoing::stillGuarding(), HowTheGuardIsGoing::ended());

    howTheGuardWasAskedFor($guarding);

    expect([whatBecameOfTheGuard($guarding), whatBecameOfTheGuard($guarding), whatBecameOfTheGuard($guarding)])->toBe(['guarding', 'ended', 'ended'])
        ->and($guarding->started())->toHaveCount(1)
        ->and($guarding->followed())->toHaveCount(3);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('WatchEnvelope', whatAStackReportsOfAGuard()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('JobEnvelope', ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'watch', 'job' => AStackThatGuards::THE_JOB]]))
        ->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', whatAStackSaysOfAGuardItCouldNotStart()))
        ->toBe([]);
});
