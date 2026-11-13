<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWayToBeTold;
use Modules\Kernel\Api\ChoosingHowTheyAreTold;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowBeingToldCameBack;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WaysToBeTold;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\WaysOfBeingTold;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AHouseThatTellsWhenItsReady;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aHouseThatTells(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('t', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the core answers a member's ways to be told with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatTheCoreOffered(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'notify-mine', 'data' => [...[
        'offered' => [['id' => 'house-ntfy', 'name' => 'The house app', 'receiving' => true], ['id' => 'mail', 'name' => 'Email', 'receiving' => false]],
        'phone' => true,
        'rehearsed' => false,
    ], ...$changed]];
}

function aTellerAnswered(MockResponse ...$answered): ChoosingHowTheyAreTold
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new WaysOfBeingTold(new PinnedClients());
}

function howTheyAreTold(HowBeingToldCameBack $came): string
{
    return $came->either(
        told: static fn(WaysToBeTold $ways): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s|%s', implode(',', array_map(
            static fn(AWayToBeTold $way): string => sprintf('%s:%s:%s', $way->id(), $way->name(), $way->receiving() ? 'told' : 'not told'),
            iterator_to_array($ways, preserve_keys: false),
        )), $ways->wakesThisPhone() ? 'phone' : 'no phone')),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** @return array{to: string, body: array<mixed>} */
function whatWasSentToChoose(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return ['to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), 'body' => is_array($body) ? $body : []];
}

it('reads the ways offered in the core\'s order, either way, and whether this phone is woken', function (): void {
    $offered = WaysToBeTold::offered(phone: true, ways: [AWayToBeTold::offered('house-ntfy', 'The house app', receiving: true), AWayToBeTold::offered('mail', 'Email', receiving: false)]);
    $either = [
        'the fake' => AHouseThatTellsWhenItsReady::offering($offered),
        'the adapter' => aTellerAnswered(MockResponse::make((string) json_encode(whatTheCoreOffered()))),
    ];

    foreach ($either as $which => $telling) {
        expect(howTheyAreTold($telling->offered(aHouseThatTells(), Session::of('a-session-not-a-secret'))))->toBe('house-ntfy:The house app:told,mail:Email:not told|phone', $which);
    }
});

it('reads a house that offers nothing as one that offers nothing', function (): void {
    expect(howTheyAreTold(aTellerAnswered(MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [], 'phone' => false]))))->offered(aHouseThatTells(), Session::of('a-session-not-a-secret'))))->toBe('|no phone');
});

it('reads its ways from the member\'s own read, and sends a choice as the ways chosen and nothing else, reading the core\'s answer either way', function (): void {
    $answered = whatTheCoreOffered(['offered' => [['id' => 'house-ntfy', 'name' => 'The house app', 'receiving' => false], ['id' => 'mail', 'name' => 'Email', 'receiving' => true]]]);
    $teller = aTellerAnswered(MockResponse::make((string) json_encode(whatTheCoreOffered())), MockResponse::make((string) json_encode($answered)));
    $fake = AHouseThatTellsWhenItsReady::offering(WaysToBeTold::offered(phone: true, ways: [AWayToBeTold::offered('house-ntfy', 'The house app', receiving: true), AWayToBeTold::offered('mail', 'Email', receiving: false)]));

    $teller->offered(aHouseThatTells(), Session::of('a-session-not-a-secret'));

    foreach (['the fake' => $fake, 'the adapter' => $teller] as $which => $telling) {
        expect(howTheyAreTold($telling->choose(aHouseThatTells(), Session::of('a-session-not-a-secret'), ['mail'])))->toBe('house-ntfy:The house app:not told,mail:Email:told|phone', $which);
    }

    [$read, $chosen] = array_map(whatWasSentToChoose(...), MockClient::global()->getRecordedResponses());

    expect($read['to'])->toBe('/api/notify/mine')
        ->and($chosen['to'])->toBe('/api/actions/notify-choose')
        ->and($chosen['body'])->toBe(['backends' => ['mail']])
        ->and($fake->chosen)->toBe([['mail']]);
});

it('comes away with the obstacle where the core would not say, or said what this app cannot read', function (MockResponse $answered, KindOfObstacle $kind): void {
    $teller = aTellerAnswered($answered, clone $answered);

    expect(howTheyAreTold($teller->offered(aHouseThatTells(), Session::of('a-session-not-a-secret'))))->toBe($kind->name)
        ->and(howTheyAreTold($teller->choose(aHouseThatTells(), Session::of('a-session-not-a-secret'), [])))->toBe($kind->name);
})->with([
    'refused' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'not json at all' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
    'another envelope' => [MockResponse::make((string) json_encode([...whatTheCoreOffered(), 'kind' => 'notify'])), KindOfObstacle::AnswerCouldNotBeRead],
    'no table at all' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'notify-mine', 'data' => 'nothing'])), KindOfObstacle::AnswerCouldNotBeRead],
    'ways that are not a list' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => 'mail']))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way that is not a table' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => ['mail']]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way with no id' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [['name' => 'Email', 'receiving' => true]]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way with a blank id' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [['id' => ' ', 'name' => 'Email', 'receiving' => true]]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way with no name' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [['id' => 'mail', 'receiving' => true]]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way with a blank name' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [['id' => 'mail', 'name' => ' ', 'receiving' => true]]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a way not saying whether they are told' => [MockResponse::make((string) json_encode(whatTheCoreOffered(['offered' => [['id' => 'mail', 'name' => 'Email']]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'no word on the phone' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'notify-mine', 'data' => ['offered' => [], 'rehearsed' => false]])), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('stands in for a house with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('NotifyMineEnvelope', whatTheCoreOffered()))->toBe([]);
});
