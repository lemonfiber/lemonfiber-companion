<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APathLeft;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Forgetting;
use Modules\Kernel\Api\ForgettingAgreed;
use Modules\Kernel\Api\ForgettingWasNotShown;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\ThePlacesGone;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereForgettingGot;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Sdk\Api\Forgetters;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatForgets;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackThatForgetsWhatItKeeps(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers removing everything with, its removal as a case says.
 *
 * @param  array<mixed>         $removal
 * @return array<string, mixed>
 */
function whatAStackSaysOfForgetting(array $removal = ['state' => 'unconfirmed']): array
{
    return ['api_version' => 1, 'kind' => 'stored', 'data' => [
        'rehearsed' => false,
        'roots' => [['at' => '/srv/lemonfiber', 'what' => 'Everything the stack writes']],
        'kept' => [['what' => 'The VPN credentials', 'at' => '/srv/lemonfiber/secrets/vpn', 'why' => 'So the tunnel can be raised', 'secret' => true]],
        'beside' => [],
        'removal' => $removal,
    ]];
}

function whatTheLoftListsToForget(): WhatThisMachineKeeps
{
    return WhatThisMachineKeeps::of(
        TheRoots::of(WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes')),
        WhatIsKept::of(SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::said(secret: true))),
        WhatIsBeside::of(),
    );
}

function theListingOfWhatWouldGo(): TheForgetting
{
    return TheForgetting::said(whatTheLoftListsToForget(), WhereForgettingGot::unconfirmed());
}

/** @return array<string, mixed> */
function nowhereToForget(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'KEPT-1',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'this run cannot say where lemonfiber keeps its own files',
        'meaning' => 'Guessing at the usual place would risk naming a directory that is somebody else\'s.',
        'remedies' => [['action' => 'Run this as the account that installed lemonfiber']],
    ]];
}

function aForgetterAnswered(MockResponse ...$answered): Forgetting
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Forgetters(new PinnedClients(), SequencedEntropy::counting());
}

function forgettingTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'forget', 'job' => $job]]), 202);
}

/**
 * @param WhatBecameOfTheWork<TheForgetting> $became
 */
function whatForgettingCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(TheForgetting $forgetting): TheWordCarriedOut => $forgetting->got()->either(
            notAsked: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not asked'),
            unconfirmed: static fn(): TheWordCarriedOut => new TheWordCarriedOut(sprintf('would go: %d kept', count($forgetting->listed()->kept()))),
            done: static function (ThePlacesGone $gone, APathLeft ...$left): TheWordCarriedOut {
                $went = [];

                foreach ($gone as $place) {
                    $went[] = $place;
                }

                return new TheWordCarriedOut(sprintf('gone: %s; left: %s', implode(', ', $went), implode(', ', array_map(static fn(APathLeft $one): string => sprintf('%s (%s)', $one->where(), $one->why()), $left))));
            },
        ),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('comes away from asking what would go with the job the stack named', function (): void {
    foreach (['the fake' => AStackThatForgets::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended()), 'the adapter' => aForgetterAnswered(forgettingTakenOn(AStackThatForgets::THE_LISTING))] as $which => $forgetting) {
        expect(whatForgettingCameTo($forgetting->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'))))
            ->toBe(sprintf('following %s', AStackThatForgets::THE_LISTING), $which);
    }
});

it('reads the listing of what would go, and what went once it was agreed to', function (array $removal, WhereForgettingGot $got, string $said): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfForgetting($removal)));
    $fake = AStackThatForgets::answering(WhatBecameOfTheWork::answered(TheForgetting::said(whatTheLoftListsToForget(), $got)), WhatBecameOfTheWork::ended());

    foreach (['the fake' => $fake, 'the adapter' => aForgetterAnswered($finished)] as $which => $forgetting) {
        expect(whatForgettingCameTo($forgetting->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named(AStackThatForgets::THE_LISTING))))
            ->toBe($said, $which);
    }
})->with([
    'the listing' => [['state' => 'unconfirmed'], WhereForgettingGot::unconfirmed(), 'would go: 1 kept'],
    'nothing asked' => [['state' => 'not-asked'], WhereForgettingGot::notAsked(), 'not asked'],
    'removed' => [
        ['state' => 'done', 'gone' => ['/home/op/.config/lemonfiber'], 'left' => [['at' => '/home/op/.local/share/lemonfiber', 'why' => 'permission denied']]],
        WhereForgettingGot::done(ThePlacesGone::of('/home/op/.config/lemonfiber'), APathLeft::at('/home/op/.local/share/lemonfiber', 'permission denied')),
        'gone: /home/op/.config/lemonfiber; left: /home/op/.local/share/lemonfiber (permission denied)',
    ],
]);

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'forget', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatForgettingCameTo(aForgetterAnswered($running)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatForgettingCameTo(aForgetterAnswered($forgotten)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(nowhereToForget()), 422);
    $said = 'refused: this run cannot say where lemonfiber keeps its own files / Run this as the account that installed lemonfiber';

    expect(whatForgettingCameTo(aForgetterAnswered($refused)->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'))))->toBe($said)
        ->and(whatForgettingCameTo(aForgetterAnswered($refused)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatForgettingCameTo(aForgetterAnswered($answered)->forget(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), ForgettingAgreed::after(theListingOfWhatWouldGo()))))->toBe($kind->name)
        ->and(whatForgettingCameTo(aForgetterAnswered($answered)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatForgettingCameTo(AStackThatForgets::sayingAtOnce(WhatBecameOfTheWork::met(Obstacle::of($kind)))->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'))))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads a removal it cannot read as an answer it could not read, never a shorter one', function (array $removal): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfForgetting($removal)));

    expect(whatForgettingCameTo(aForgetterAnswered($finished)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'a state this app does not read' => [['state' => 'halfway']],
    'a place gone that is blank' => [['state' => 'done', 'gone' => [' '], 'left' => []]],
    'a place left that is not a row' => [['state' => 'done', 'gone' => [], 'left' => ['permission denied']]],
    'a place left with no reason' => [['state' => 'done', 'gone' => [], 'left' => [['at' => '/srv/lemonfiber']]]],
]);

it('reads a listing whose data is not an object as an answer it could not read', function (): void {
    $finished = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'stored', 'data' => 'everything']));

    expect(whatForgettingCameTo(aForgetterAnswered($finished)->whatBecameOf(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('reads a handle it cannot follow as an answer it could not read', function (): void {
    expect(whatForgettingCameTo(aForgetterAnswered(forgettingTakenOn(' '))->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

/** @return array<mixed> */
function whatWasSentToForget(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        ...(is_array($body) ? $body : []),
    ];
}

it('asks without the yes first, and sends the yes only against the listing', function (): void {
    $forgetters = aForgetterAnswered(forgettingTakenOn('a'), forgettingTakenOn('b'));
    $forgetters->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'));
    $forgetters->forget(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), ForgettingAgreed::after(theListingOfWhatWouldGo()));

    [$asking, $yes] = array_map(whatWasSentToForget(...), MockClient::global()->getRecordedResponses());

    expect($asking)->toMatchArray(['to' => '/api/actions/forget', 'confirm' => false])
        ->and($yes)->toMatchArray(['to' => '/api/actions/forget', 'confirm' => true])
        ->and(static fn(): ForgettingAgreed => ForgettingAgreed::after(TheForgetting::said(whatTheLoftListsToForget(), WhereForgettingGot::notAsked())))->toThrow(ForgettingWasNotShown::class);
});

it('the fake remembers each asking and each yes', function (): void {
    $forgetting = AStackThatForgets::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    $agreed = ForgettingAgreed::after(theListingOfWhatWouldGo());
    $forgetting->whatWouldGo(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'));
    $forgetting->forget(aStackThatForgetsWhatItKeeps(), Session::of('a-session-not-a-secret'), $agreed);

    expect($forgetting->askedWhatWouldGo())->toBe(1)
        ->and($forgetting->agreed())->toBe([$agreed])
        ->and($agreed->shown()->got()->awaitsTheYes())->toBeTrue();
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StoredEnvelope', whatAStackSaysOfForgetting()))
        ->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('StoredEnvelope', whatAStackSaysOfForgetting(['state' => 'done', 'gone' => ['/srv/lemonfiber'], 'left' => [['at' => '/srv/x', 'why' => 'busy']]])))
        ->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', nowhereToForget()))
        ->toBe([]);
});
