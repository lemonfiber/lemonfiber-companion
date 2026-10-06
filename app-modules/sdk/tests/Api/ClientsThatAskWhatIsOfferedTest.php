<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;

use ArrayObject;

use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Api\ClientsThatAskWhatIsOffered;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Supervisors;
use Modules\Sdk\Api\TheStackDoesNotOfferIt;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_ends_with;
use function str_repeat;

use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * The gate every request passes: a path the stack does not declare is refused
 * before it is sent, and what the operator met is said as itself.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The moment the gate is asked at. */
const THE_GATE_IS_ASKED_AT = 1_790_000_000;

/** The stack behind the gate. */
function aStackBehindTheGate(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The session the gate is asked with. */
function theSessionAtTheGate(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What a stack declares it serves, as it answers.
 *
 * @param array<string, string> $capabilities
 */
function whatTheStackAtTheGateDeclares(array $capabilities): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'capabilities', 'data' => ['capabilities' => $capabilities]]));
}

/** A stack older than the declaration. */
function aStackAtTheGateWithNoDeclaration(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => aRefusalOfAPathNobodyServes()]), 404);
}

/** @return array<string, mixed> */
function aRefusalOfAPathNobodyServes(): array
{
    return [
        'code' => 'ASK-9',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'There is nothing at that path',
        'meaning' => 'This lemonfiber serves no request there.',
        'remedies' => [['action' => 'Check the path against the API this lemonfiber serves']],
    ];
}

/** An update a reading offered. */
function anUpdateAtTheGate(): TakingAnUpdate
{
    return TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    ));
}

/** The clients that ship, asking first, over a stack that declares this. */
function clientsAskingAStackThatDeclares(MockResponse $declaration): ClientsThatAskWhatIsOffered
{
    MockClient::destroyGlobal();
    MockClient::global(['*' => static fn(PendingRequest $asked): MockResponse => str_ends_with($asked->getUrl(), Api::CAPABILITIES_ENDPOINT)
        ? $declaration
        : MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restart', 'job' => 'a-job']]), 202)]);

    return new ClientsThatAskWhatIsOffered(new PinnedClients(), new WhatEachStackOffers(), FrozenClock::at(Instant::atEpochSeconds(THE_GATE_IS_ASKED_AT)));
}

/**
 * What asking the gate for this path came to: the obstacle it refused with, the stack's silence, that the
 * reading waits a frame, or that it let it through.
 */
function whatTheGateSaidOf(ClientsThatAskWhatIsOffered $clients, string $path): string
{
    try {
        $clients->towards(aStackBehindTheGate(), theSessionAtTheGate(), Ability::of($path));

        return 'let through';
    } catch (TheStackDoesNotOfferIt $why) {
        return $clients->whatStoodInTheWay(aStackBehindTheGate(), $why)->kind()->name;
    } catch (Unreachable) {
        return 'the stack did not answer';
    } catch (TheReadingWaitsAFrame) {
        return 'waits a frame';
    }
}

/** What asking the gate for the status reading came to. */
function whatTheGateSaidOfTheStatus(ClientsThatAskWhatIsOffered $clients): string
{
    return whatTheGateSaidOf($clients, Api::STATUS_ENDPOINT);
}

/** What asking the gate for a restart came to. */
function whatTheGateSaidOfARestart(ClientsThatAskWhatIsOffered $clients): string
{
    return whatTheGateSaidOf($clients, Api::action(WhatToDoWithIt::Restart->asked()));
}

it('refuses a request the stack is too old for, as the stack being too old for it', function (): void {
    expect(whatTheGateSaidOfARestart(clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([]))))
        ->toBe(KindOfObstacle::NotOnThisStack->name);
});

it('refuses a request the account may not make, as not this account\'s to ask', function (): void {
    expect(whatTheGateSaidOfARestart(clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([Api::action(WhatToDoWithIt::Restart->asked()) => 'unpermitted']))))
        ->toBe(KindOfObstacle::NotForThisAccount->name);
});

it('sends an action the stack offers, and one it has and has not set up, on the frame after the one that asked', function (MockResponse $declaration): void {
    $clients = clientsAskingAStackThatDeclares($declaration);

    expect(whatTheGateSaidOfARestart($clients))->toBe('waits a frame')
        ->and(whatTheGateSaidOfARestart($clients))->toBe('let through')
        ->and(MockClient::global()->getRecordedResponses())->toHaveCount(1);
})->with([
    'offered' => [fn(): MockResponse => whatTheStackAtTheGateDeclares([Api::action(WhatToDoWithIt::Restart->asked()) => 'available'])],
    'not set up' => [fn(): MockResponse => whatTheStackAtTheGateDeclares([Api::action(WhatToDoWithIt::Restart->asked()) => 'unconfigured'])],
]);

it('puts a reading off to the next frame where the frame asked the stack, and sends it there without asking again', function (): void {
    $clients = clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([Api::STATUS_ENDPOINT => 'available']));

    expect(whatTheGateSaidOfTheStatus($clients))->toBe('waits a frame')
        ->and(MockClient::global()->getRecordedResponses())->toHaveCount(1)
        ->and(whatTheGateSaidOfTheStatus($clients))->toBe('let through')
        ->and(MockClient::global()->getRecordedResponses())->toHaveCount(1);
});

it('refuses a reading the stack is too old for on the frame that asked, which sends nothing more', function (): void {
    expect(whatTheGateSaidOfTheStatus(clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([]))))
        ->toBe(KindOfObstacle::NotOnThisStack->name);
});

it('gives a reading that asked a silent stack that silence', function (): void {
    expect(whatTheGateSaidOfTheStatus(clientsAskingAStackThatDeclares(MockResponse::make()->throw(static fn(PendingRequest $asked): Unreachable
        => Unreachable::whenAsking($asked->getRequest()->resolveEndpoint(), 'Connection timed out')))))
        ->toBe('the stack did not answer');
});

it('gives the request that asked a silent stack that silence, and lets the next one through without asking again', function (): void {
    $clients = clientsAskingAStackThatDeclares(MockResponse::make()->throw(static fn(PendingRequest $asked): Unreachable
        => Unreachable::whenAsking($asked->getRequest()->resolveEndpoint(), 'Connection timed out')));

    expect(whatTheGateSaidOfARestart($clients))->toBe('the stack did not answer')
        ->and(whatTheGateSaidOfARestart($clients))->toBe('let through')
        ->and(MockClient::global()->getRecordedResponses())->toHaveCount(0);
});

it('lets taking an update through on a stack too old to say what it serves, and nothing else', function (): void {
    $clients = clientsAskingAStackThatDeclares(aStackAtTheGateWithNoDeclaration());

    expect(whatTheGateSaidOf($clients, Api::action(anUpdateAtTheGate()->asked())))->toBe('waits a frame')
        ->and(whatTheGateSaidOf($clients, Api::action(anUpdateAtTheGate()->asked())))->toBe('let through')
        ->and(whatTheGateSaidOfARestart($clients))->toBe(KindOfObstacle::NotOnThisStack->name);
});

it('hands out a client for what is not a request it offers without asking', function (): void {
    /** @var ArrayObject<int, string> $asked */
    $asked = new ArrayObject();
    MockClient::destroyGlobal();
    MockClient::global(['*' => static function (PendingRequest $request) use ($asked): MockResponse {
        $asked[] = $request->getUrl();

        return whatTheStackAtTheGateDeclares([]);
    }]);
    $clients = new ClientsThatAskWhatIsOffered(new PinnedClients(), new WhatEachStackOffers(), FrozenClock::at(Instant::atEpochSeconds(THE_GATE_IS_ASKED_AT)));

    expect($clients->client(aStackBehindTheGate(), theSessionAtTheGate()))->toBeInstanceOf(Client::class)
        ->and($asked->count())->toBe(0);
});

it('says what stood in the way of a reach the way the clients behind it do', function (): void {
    $clients = clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([]));

    expect($clients->whatStoodInTheWay(aStackBehindTheGate(), Unreachable::whenAsking('/api/status', 'Connection refused')))
        ->toEqual(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
});

it('never sends an adapter\'s action to a stack too old for it, and says why on its screen', function (): void {
    $supervisors = new Supervisors(clientsAskingAStackThatDeclares(whatTheStackAtTheGateDeclares([])), SequencedEntropy::counting());

    $said = $supervisors->told(aStackBehindTheGate(), theSessionAtTheGate(), AgreedTo::theForm(WhatToDoWithIt::Restart, Form::called('library')))->either(
        started: static fn(): TheWordCarriedOut => new TheWordCarriedOut('sent'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;

    expect($said)->toBe(KindOfObstacle::NotOnThisStack->name)
        ->and(MockClient::global()->getRecordedResponses())->toHaveCount(1);
});

it('stands in for a stack with a declaration and a refusal the contract would accept', function (): void {
    $declaration = ['api_version' => 1, 'kind' => 'capabilities', 'data' => ['capabilities' => [
        Api::action(WhatToDoWithIt::Restart->asked()) => 'unpermitted',
    ]]];
    $refusal = ['api_version' => 1, 'kind' => 'error', 'data' => aRefusalOfAPathNobodyServes()];

    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', $declaration))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', $refusal))->toBe([]);
});
