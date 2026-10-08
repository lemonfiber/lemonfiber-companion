<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ChoosingAFiller;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;
use Modules\Sdk\Api\Fillers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatChoosesFillers;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The ChoosingAFiller contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a choice of filler is asked of. */
function aStackChoosingAFiller(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The name the stack's reading goes by. */
const THE_READING_IS_NAMED = '1a2b3c4d-5e6f7a8b-9c0d1e2f-0a0b0c0d';

/**
 * What a stack answers about a choice of filler, changed where a case says, and
 * without the fields of the choice a case leaves out altogether.
 *
 * @param  array<mixed>         $changed  replacing fields of the payload
 * @param  array<mixed>         $choice   replacing fields of the choice
 * @param  array<mixed>         $without  fields of the choice left out
 * @return array<string, mixed>
 */
function whatAStackSaysAFillComesTo(array $changed = [], array $choice = [], array $without = []): array
{
    $substitution = [
        'asked_by' => ['seerr', 'bazarr'],
        'capability' => 'media-server',
        'leaves_unfilled' => [['by' => 'tdarr', 'capability' => 'transcoder']],
        'now' => 'plex',
        'setting' => 'media-server=plex',
        'was' => 'jellyfin',
        ...$choice,
    ];

    return ['api_version' => 1, 'kind' => 'substitution', 'data' => [
        'agreement' => THE_READING_IS_NAMED,
        'applied' => false,
        'rehearsed' => false,
        'substitution' => array_filter($substitution, static fn(string $field): bool => ! in_array($field, $without, strict: true), ARRAY_FILTER_USE_KEY),
        ...$changed,
    ]];
}

/** The reading that payload stands for, as the fake is handed it. */
function theSameFillWorkedOut(): AFill
{
    return AFill::read(
        Capability::called('media-server'),
        ServiceId::called('plex'),
        Services::these(ServiceId::called('jellyfin')),
        Services::these(ServiceId::called('seerr'), ServiceId::called('bazarr')),
        WhatNothingFills::these(Unfilled::of(ServiceId::called('tdarr'), Capability::called('transcoder'))),
        '',
        THE_READING_IS_NAMED,
    );
}

/**
 * The stack refusing a choice, as the error envelope it answers with.
 *
 * @return array<string, mixed>
 */
function aChoiceTheStackRefuses(string $code, string $severity = 'error'): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => $code,
        'severity' => $severity,
        'state' => 'guided',
        'summary' => 'That choice was not made',
        'meaning' => 'Since it was read, what fills it now changed, so nothing was changed.',
        'remedies' => [['action' => 'Read the choice again, and answer the name it prints']],
        'detail' => 'the offer standing now is 0a0b0c0d-1a1b1c1d-2a2b2c2d-3a3b3c3d',
    ]];
}

/** The adapter, answered with these responses in turn. */
function aFillerAnswered(MockResponse ...$answered): ChoosingAFiller
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Fillers(new PinnedClients(), SequencedEntropy::counting());
}

/**
 * Both ways of choosing a filler, each set up to say the same.
 *
 * @return array<string, Closure(): ChoosingAFiller>
 */
function everyWayOfChoosingAFiller(MockResponse $answered, WhatBecameOfTheFill $same): array
{
    return [
        'the fake' => static fn(): ChoosingAFiller => AStackThatChoosesFillers::answering($same),
        'the adapter' => static fn(): ChoosingAFiller => aFillerAnswered($answered),
    ];
}

/** Every service named, joined by a plus, or `nothing` where there is none. */
function theServicesAFillNames(Services $services): string
{
    $named = [];

    foreach ($services as $service) {
        $named[] = $service->named();
    }

    return $named === [] ? 'nothing' : implode('+', $named);
}

/** What a choice came to, every part folded to one line. */
function whatTheChoiceCameTo(WhatBecameOfTheFill $became): string
{
    return $became->either(
        fill: static function (AFill $fill): TheWordCarriedOut {
            $leaving = [];

            foreach ($fill->leaves() as $left) {
                $leaving[] = sprintf('%s loses %s', $left->asking()->named(), $left->capability()->named());
            }

            return new TheWordCarriedOut(sprintf(
                '%s: %s answers %s, was %s, asked by %s, leaves %s, why "%s", named %s',
                $fill->wasMade() ? 'made' : 'read',
                $fill->now()->named(),
                $fill->capability()->named(),
                theServicesAFillNames($fill->was()),
                theServicesAFillNames($fill->askedBy()),
                implode('+', $leaving),
                $fill->why(),
                $fill->agreement(),
            ));
        },
        turnedDown: static fn(AFillTurnedDown $down): TheWordCarriedOut => new TheWordCarriedOut(sprintf('turned down: %s | %s', $down->why()->name, $down->said()->meaning())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s | %s | %s', $why->summary(), $why->meaning(), $why->named()->forTheOperator())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** What asking what filling media-server with plex would come to came to. */
function whatFillingItWouldComeTo(ChoosingAFiller $choosing): string
{
    return whatTheChoiceCameTo($choosing->whatItWouldComeTo(aStackChoosingAFiller(), Session::of('a-session-not-a-secret'), Capability::called('media-server'), ServiceId::called('plex')));
}

/** What agreeing to the reading came to, with the reason given. */
function whatAgreeingCameTo(ChoosingAFiller $choosing, string $because = 'Plex plays the 4K files'): string
{
    return whatTheChoiceCameTo($choosing->choose(aStackChoosingAFiller(), Session::of('a-session-not-a-secret'), AFillAgreed::after(theSameFillWorkedOut(), $because)));
}

it('reads what a choice would come to, writing nothing', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysAFillComesTo()));

    foreach (everyWayOfChoosingAFiller($answered, WhatBecameOfTheFill::fill(theSameFillWorkedOut())) as $which => $build) {
        expect(whatFillingItWouldComeTo($build()))
            ->toBe(sprintf('read: plex answers media-server, was jellyfin, asked by seerr+bazarr, leaves tdarr loses transcoder, why "", named %s', THE_READING_IS_NAMED), $which);
    }
});

it('reads a choice the stack made, with the reason it recorded', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysAFillComesTo(['applied' => true], ['why' => 'Plex plays the 4K files'])));
    $made = AFill::made(
        Capability::called('media-server'),
        ServiceId::called('plex'),
        Services::these(ServiceId::called('jellyfin')),
        Services::these(ServiceId::called('seerr'), ServiceId::called('bazarr')),
        WhatNothingFills::these(Unfilled::of(ServiceId::called('tdarr'), Capability::called('transcoder'))),
        'Plex plays the 4K files',
        THE_READING_IS_NAMED,
    );

    foreach (everyWayOfChoosingAFiller($answered, WhatBecameOfTheFill::fill($made)) as $which => $build) {
        expect(whatAgreeingCameTo($build()))
            ->toBe(sprintf('made: plex answers media-server, was jellyfin, asked by seerr+bazarr, leaves tdarr loses transcoder, why "Plex plays the 4K files", named %s', THE_READING_IS_NAMED), $which);
    }
});

it('reads nothing answering now and nothing left unfilled as answers, absent or null', function (array $choice, array $without): void {
    expect(whatFillingItWouldComeTo(aFillerAnswered(MockResponse::make((string) json_encode(whatAStackSaysAFillComesTo(choice: $choice, without: $without))))))
        ->toBe(sprintf('read: plex answers media-server, was nothing, asked by seerr+bazarr, leaves , why "", named %s', THE_READING_IS_NAMED));
})->with([
    'left out' => [['leaves_unfilled' => []], ['was']],
    'null' => [['leaves_unfilled' => [], 'was' => null, 'why' => null], []],
]);

it('reads each refusal of a choice by its code, in words of its own', function (string $code, int $status, string $severity, WhyTheFillWasTurnedDown $why): void {
    $answered = MockResponse::make((string) json_encode(aChoiceTheStackRefuses($code, $severity)), $status);
    $said = ARefusalInItsWords::said('That choice was not made', 'Since it was read, what fills it now changed, so nothing was changed.', WhatTheRefusalNamed::nothing());

    foreach (everyWayOfChoosingAFiller($answered, WhatBecameOfTheFill::turnedDown(AFillTurnedDown::because($why, $said))) as $which => $build) {
        expect(whatAgreeingCameTo($build()))->toBe(sprintf('turned down: %s | Since it was read, what fills it now changed, so nothing was changed.', $why->name), $which);
    }
})->with([
    'no such service' => ['WIRE-1', 404, 'error', WhyTheFillWasTurnedDown::NoSuchService],
    'a service that cannot fill it' => ['WIRE-2', 400, 'error', WhyTheFillWasTurnedDown::CannotFill],
    'a service that fills it already' => ['WIRE-2', 400, 'advisory', WhyTheFillWasTurnedDown::AlreadyFills],
    'nothing asking for it' => ['WIRE-3', 400, 'warning', WhyTheFillWasTurnedDown::NothingAsks],
    'nowhere to keep it' => ['WIRE-4', 500, 'error', WhyTheFillWasTurnedDown::NowhereToKeepIt],
    'a reading that moved' => ['WIRE-5', 400, 'error', WhyTheFillWasTurnedDown::Moved],
    'a reason that cannot be kept' => ['WIRE-6', 400, 'error', WhyTheFillWasTurnedDown::ReasonCannotBeKept],
]);

it('reads any other refusal in the stack\'s words, with what it named', function (): void {
    $answered = MockResponse::make((string) json_encode(aChoiceTheStackRefuses('PLUGIN-4')), 500);
    $said = ARefusalInItsWords::said('That choice was not made', 'Since it was read, what fills it now changed, so nothing was changed.', WhatTheRefusalNamed::as('the offer standing now is 0a0b0c0d-1a1b1c1d-2a2b2c2d-3a3b3c3d'));

    foreach (everyWayOfChoosingAFiller($answered, WhatBecameOfTheFill::refused($said)) as $which => $build) {
        expect(whatAgreeingCameTo($build()))
            ->toBe('refused: That choice was not made | Since it was read, what fills it now changed, so nothing was changed. | the offer standing now is 0a0b0c0d-1a1b1c1d-2a2b2c2d-3a3b3c3d', $which);
    }
});

it('tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfChoosingAFiller($answered, WhatBecameOfTheFill::met($why)) as $which => $build) {
            expect(whatFillingItWouldComeTo($build()))->toBe($why->kind()->name, $which);
        }
    }
});

it('a choice this app cannot read is a stack that did not answer, never a smaller cost', function (array $changed, array $choice, array $without): void {
    expect(whatFillingItWouldComeTo(aFillerAnswered(MockResponse::make((string) json_encode(whatAStackSaysAFillComesTo($changed, $choice, $without))))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'no name for the reading' => [['agreement' => ' '], [], []],
    'no word on whether it was made' => [['applied' => 'yes'], [], []],
    'no choice' => [['substitution' => null], [], []],
    'no capability' => [[], [], ['capability']],
    'nothing to answer it after' => [[], ['now' => ' '], []],
    'something answering now with no name' => [[], ['was' => ' '], []],
    'a blank reason' => [[], ['why' => ' '], []],
    'nobody asking' => [[], [], ['asked_by']],
    'an asker with no name' => [[], ['asked_by' => [' ']], []],
    'no word on what it leaves' => [[], [], ['leaves_unfilled']],
    'a loss that is not a row' => [[], ['leaves_unfilled' => ['tdarr']], []],
    'a loss naming nobody' => [[], ['leaves_unfilled' => [['capability' => 'transcoder']]], []],
    'a loss naming no capability' => [[], ['leaves_unfilled' => [['by' => 'tdarr']]], []],
]);

it('an answer whose data is not a table, or of another kind, is a stack that did not answer', function (array $answered): void {
    expect(whatFillingItWouldComeTo(aFillerAnswered(MockResponse::make((string) json_encode($answered)))))->toBe(KindOfObstacle::StackDidNotAnswer->name);
})->with([
    'data that is not a table' => [['api_version' => 1, 'kind' => 'substitution', 'data' => 'plex']],
    'an answer of another kind' => [['api_version' => 1, 'kind' => 'wiring', 'data' => []]],
]);

it('asks for the reading naming no offer, and writes with the offer and the reason', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not json at all'), MockResponse::make('not json at all')]);
    $filler = new Fillers(new PinnedClients(), SequencedEntropy::counting());

    whatFillingItWouldComeTo($filler);
    $reading = $mock->getLastPendingRequest();
    whatAgreeingCameTo($filler);
    $agreeing = $mock->getLastPendingRequest();

    expect($reading?->getUrl())->toEndWith('/api/actions/wiring-fill')
        ->and($reading?->body()?->all())->toBe(['service' => 'plex', 'offer' => null, 'reason' => null, 'capability' => 'media-server'])
        ->and($agreeing?->body()?->all())->toBe(['service' => 'plex', 'offer' => THE_READING_IS_NAMED, 'reason' => 'Plex plays the 4K files', 'capability' => 'media-server']);
});

it('sends no reason where none was given', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not json at all')]);

    whatAgreeingCameTo(new Fillers(new PinnedClients(), SequencedEntropy::counting()), '  ');

    expect($mock->getLastPendingRequest()?->body()?->all())->toBe(['service' => 'plex', 'offer' => THE_READING_IS_NAMED, 'reason' => null, 'capability' => 'media-server']);
});

it('the fake remembers every question, in order', function (): void {
    $choosing = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(theSameFillWorkedOut()));
    whatFillingItWouldComeTo($choosing);
    whatAgreeingCameTo($choosing);

    expect($choosing->asked())->toBe([
        'would plex answer media-server',
        sprintf('choose plex for media-server, agreeing to %s, because "Plex plays the 4K files"', THE_READING_IS_NAMED),
    ]);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SubstitutionEnvelope', whatAStackSaysAFillComesTo()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('SubstitutionEnvelope', whatAStackSaysAFillComesTo(['applied' => true], ['why' => 'Plex plays the 4K files'])))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aChoiceTheStackRefuses('WIRE-5')))->toBe([]);
});
