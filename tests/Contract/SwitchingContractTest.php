<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Switching;
use Modules\Kernel\Api\SwitchNamesNoForm;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Switchers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatSwitches;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWhoseFormsSwitch(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a switch with, its `switched` changed where a case says.
 *
 * @param  array<mixed>         $switched
 * @param  array<mixed>         $instead
 * @return array<string, mixed>
 */
function whatAStackSaysOfASwitch(array $switched = [], bool $rehearsed = true, array $instead = []): array
{
    return ['api_version' => 1, 'kind' => 'lifecycle', 'data' => [
        'action' => 'switch',
        'command' => ['docker', 'compose', '--profile', 'media', 'up', '-d'],
        'plan' => [
            'forms' => ['library'],
            'profiles' => ['media'],
            'services' => ['jellyfin'],
            'dropped' => [],
            'filtered' => [],
            'footprint' => ['estimated_mib' => 600, 'unestimated' => []],
        ],
        'rehearsed' => $rehearsed,
        'services' => [],
        'stack_edits' => [],
        'switched' => [
            'stopped' => ['qbittorrent'],
            'started' => ['jellyseerr'],
            'kept' => ['jellyfin'],
            'stop_command' => ['docker', 'compose', 'stop', 'qbittorrent'],
            ...$switched,
        ],
        ...$instead,
    ]];
}

function theSameRehearsedSwitch(): TheSwitch
{
    return TheSwitch::said(WhetherItWasRehearsed::Rehearsed, TheServicesNamed::of('qbittorrent'), TheServicesNamed::of('jellyseerr'), TheServicesNamed::of('jellyfin'))
        ->stoppingWith(TheCommandLine::of('docker', 'compose', 'stop', 'qbittorrent'));
}

/** @return array<string, mixed> */
function aFormTheStackDoesNotDeclare(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'FORM-2',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'this stack declares no form called archive',
        'meaning' => 'A switch to a form nobody declared would stop everything and start nothing.',
        'remedies' => [['action' => 'Choose one of: library, downloads']],
    ]];
}

function aSwitcherAnswered(MockResponse ...$answered): Switching
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Switchers(new PinnedClients(), SequencedEntropy::counting());
}

function aSwitchTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'switch', 'job' => $job]]), 202);
}

/**
 * @param WhatBecameOfTheWork<TheSwitch> $became
 */
function whatTheSwitchCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(TheSwitch $switch): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|stops %s|starts %s|keeps %s|%s',
            $switch->did()->value,
            implode(',', iterator_to_array($switch->stopped(), preserve_keys: false)),
            implode(',', iterator_to_array($switch->started(), preserve_keys: false)),
            implode(',', iterator_to_array($switch->kept(), preserve_keys: false)),
            $switch->stopCommand(
                with: static fn(TheCommandLine $command): TheWordCarriedOut => new TheWordCarriedOut($command->asTyped()),
                none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no stop'),
            )->said,
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

function theLibraryAlone(): TheFormsToSwitchTo
{
    return TheFormsToSwitchTo::these(Form::called('library'));
}

it('comes away from rehearsing with the job the stack named', function (): void {
    foreach (['the fake' => AStackThatSwitches::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended()), 'the adapter' => aSwitcherAnswered(aSwitchTakenOn(AStackThatSwitches::THE_REHEARSAL))] as $which => $switching) {
        expect(whatTheSwitchCameTo($switching->rehearse(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), theLibraryAlone())))
            ->toBe(sprintf('following %s', AStackThatSwitches::THE_REHEARSAL), $which);
    }
});

it('reads what it would stop, start and keep, the rehearsal labelled as one', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfASwitch()));

    foreach (['the fake' => AStackThatSwitches::answering(WhatBecameOfTheWork::answered(theSameRehearsedSwitch()), WhatBecameOfTheWork::ended()), 'the adapter' => aSwitcherAnswered($finished)] as $which => $switching) {
        expect(whatTheSwitchCameTo($switching->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSwitches::THE_REHEARSAL))))
            ->toBe('rehearsed|stops qbittorrent|starts jellyseerr|keeps jellyfin|docker compose stop qbittorrent', $which);
    }
});

it('reads a switch that stopped nothing as one with no stop command', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfASwitch(['stopped' => [], 'stop_command' => null], rehearsed: false)));

    expect(whatTheSwitchCameTo(aSwitcherAnswered($finished)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe('carried_out|stops |starts jellyseerr|keeps jellyfin|no stop');
});

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'switch', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatTheSwitchCameTo(aSwitcherAnswered($running)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatTheSwitchCameTo(aSwitcherAnswered($forgotten)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(aFormTheStackDoesNotDeclare()), 422);
    $said = 'refused: this stack declares no form called archive / Choose one of: library, downloads';

    expect(whatTheSwitchCameTo(aSwitcherAnswered($refused)->rehearse(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), TheFormsToSwitchTo::these(Form::called('archive')))))->toBe($said)
        ->and(whatTheSwitchCameTo(aSwitcherAnswered($refused)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatTheSwitchCameTo(aSwitcherAnswered($answered)->carryOut(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), theLibraryAlone())))->toBe($kind->name)
        ->and(whatTheSwitchCameTo(aSwitcherAnswered($answered)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatTheSwitchCameTo(AStackThatSwitches::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of($kind)))->carryOut(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), theLibraryAlone())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads a switch it cannot read as an answer it could not read, never a shorter one', function (array $changed): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfASwitch(instead: $changed)));

    expect(whatTheSwitchCameTo(aSwitcherAnswered($finished)->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no switched at all' => [['switched' => null]],
    'a service with no name' => [['switched' => ['stopped' => [' '], 'started' => [], 'kept' => []]]],
    'a stop command with a blank word' => [['switched' => ['stopped' => ['qbittorrent'], 'started' => [], 'kept' => [], 'stop_command' => ['docker', '']]]],
    'a stop command with a word of only spaces' => [['switched' => ['stopped' => ['qbittorrent'], 'started' => [], 'kept' => [], 'stop_command' => ['docker', '  ']]]],
    'a stop command with no words' => [['switched' => ['stopped' => ['qbittorrent'], 'started' => [], 'kept' => [], 'stop_command' => []]]],
]);

it('reads a handle it cannot follow as an answer it could not read', function (): void {
    expect(whatTheSwitchCameTo(aSwitcherAnswered(aSwitchTakenOn(' '))->carryOut(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), theLibraryAlone())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

/** @return array<mixed> */
function whatWasSentToSwitch(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        ...(is_array($body) ? $body : []),
    ];
}

it('sends the forms chosen, the rehearsal as a dry run and the yes without one, and refuses a switch to nothing', function (): void {
    $switchers = aSwitcherAnswered(aSwitchTakenOn('a'), aSwitchTakenOn('b'));
    $forms = TheFormsToSwitchTo::these(Form::called('library'), Form::called('downloads'));
    $switchers->rehearse(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), $forms);
    $switchers->carryOut(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), $forms);

    [$rehearsal, $yes] = array_map(whatWasSentToSwitch(...), MockClient::global()->getRecordedResponses());

    expect($rehearsal)->toMatchArray(['to' => '/api/actions/switch', 'dry_run' => true, 'forms' => ['library', 'downloads']])
        ->and($yes)->toMatchArray(['to' => '/api/actions/switch', 'forms' => ['library', 'downloads']])
        ->and($yes['dry_run'] ?? false)->toBeFalse()
        ->and(static fn(): TheFormsToSwitchTo => TheFormsToSwitchTo::these())->toThrow(SwitchNamesNoForm::class);
});

it('the fake remembers each set rehearsed and carried out', function (): void {
    $switching = AStackThatSwitches::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    $forms = theLibraryAlone();
    $switching->rehearse(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), $forms);
    $switching->carryOut(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), $forms);
    $switching->whatBecameOf(aStackWhoseFormsSwitch(), Session::of('a-session-not-a-secret'), Job::named(AStackThatSwitches::THE_SWITCH));

    expect($switching->rehearsed())->toBe([$forms])
        ->and($switching->carriedOut())->toBe([$forms])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $switching->followed()))->toBe([AStackThatSwitches::THE_SWITCH]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('LifecycleEnvelope', whatAStackSaysOfASwitch()))
        ->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aFormTheStackDoesNotDeclare()))
        ->toBe([]);
});
