<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatRegisteringAWakeCameTo;
use Modules\Kernel\Api\WhatRemovingAWakeCameTo;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PushEndpointKeepers;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsWakes;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The RegisteringAWake contract, run against the adapter and against the fake.
//
// Every test of turning waking on and off runs over `AStackThatKeepsWakes`, so
// what the fake promises is what those tests are written against: the endpoint
// kept is answered by the fingerprint the stack names it by, a name another holds is refused in the
// stack's words, a name held by nothing is removed all the same, and a stack
// that cannot be reached is what stood in the way.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The name this phone's endpoint is registered under in these tests. */
const THE_PHONES_PUSH_NAME = 'galaxy-a51-7f3a';

/** The endpoint the distributor gave in these tests. */
const THE_PHONES_ENDPOINT = 'https://ntfy.sh/upAbC123?up=1';

/** The first sixteen hexadecimal digits of the SHA-256 of that endpoint, as a stack names it. */
const THE_PHONES_FINGERPRINT = 'd3486aeb5bbbae8b';

function aStackThatWakesAPhone(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function theWakeToRegister(): AWakeToRegister
{
    return new AWakeToRegister(
        APushName::of(THE_PHONES_PUSH_NAME),
        AnEndpoint::of(THE_PHONES_ENDPOINT),
        DistributorsOnAPhone::key(),
        AWakingSecret::from(KeyMaterial::of(str_repeat('s', KeyMaterial::BYTES))),
    );
}

/**
 * What a stack answers a registration with.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfARegisteredWake(): array
{
    return ['api_version' => 1, 'kind' => 'push-endpoint', 'data' => ['fingerprint' => THE_PHONES_FINGERPRINT, 'host' => 'ntfy.sh', 'name' => THE_PHONES_PUSH_NAME, 'registered' => 1_793_872_800, 'rehearsed' => false]];
}

/**
 * What a stack answers a removal with.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfARemovedWake(): array
{
    return ['api_version' => 1, 'kind' => 'push-endpoints', 'data' => ['endpoints' => [['fingerprint' => '0f4e2a91c7b35d68', 'host' => 'ntfy.sh', 'name' => 'iphone-12-mini-01bc', 'registered' => 1_793_786_400]], 'rehearsed' => false, 'removed' => THE_PHONES_PUSH_NAME]];
}

/**
 * A problem document a stack refuses with.
 *
 * @return array<string, mixed>
 */
function aWakeRefusedWith(string $code, string $summary): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => ['code' => $code, 'summary' => $summary, 'meaning' => 'The endpoint was not registered.', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []]];
}

/**
 * Both ways of registering a wake: the fake as given, and the adapter answered with these.
 *
 * @return array<string, Closure(): RegisteringAWake>
 */
function everyWayOfRegisteringAWake(AStackThatKeepsWakes $fake, MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): RegisteringAWake => $fake,
        'the adapter' => static function () use ($answered): RegisteringAWake {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new PushEndpointKeepers(new PinnedClients());
        },
    ];
}

function whatRegisteringCameTo(WhatRegisteringAWakeCameTo $came): string
{
    return $came->either(
        registered: static fn(AWakeFingerprint $held): TheWordCarriedOut => new TheWordCarriedOut(sprintf('registered: %s', $held->shown())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

function whatRemovingCameTo(WhatRemovingAWakeCameTo $came): string
{
    return $came->either(
        removed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('removed'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('registers the endpoint and answers the fingerprint the stack keeps it by', function (): void {
    foreach (everyWayOfRegisteringAWake(AStackThatKeepsWakes::reachable(), MockResponse::make(whatAStackSaysOfARegisteredWake())) as $which => $make) {
        expect(whatRegisteringCameTo($make()->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
            ->toBe(sprintf('registered: %s', THE_PHONES_FINGERPRINT), $which);
    }
});

it('says a name another endpoint holds in the stack\'s words', function (): void {
    $ways = everyWayOfRegisteringAWake(
        AStackThatKeepsWakes::reachable()->alreadyHolds(aStackThatWakesAPhone(), THE_PHONES_PUSH_NAME, 'https://ntfy.sh/upOld?up=1'),
        MockResponse::make(aWakeRefusedWith('PUSH-2', AStackThatKeepsWakes::NAME_TAKEN), 409),
    );

    foreach ($ways as $which => $make) {
        expect(whatRegisteringCameTo($make()->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
            ->toBe(sprintf('refused: %s', AStackThatKeepsWakes::NAME_TAKEN), $which);
    }
});

it('says an address that is not https in the stack\'s words', function (): void {
    foreach (everyWayOfRegisteringAWake(AStackThatKeepsWakes::refusingTheAddress(), MockResponse::make(aWakeRefusedWith('PUSH-3', AStackThatKeepsWakes::NOT_HTTPS), 400)) as $which => $make) {
        expect(whatRegisteringCameTo($make()->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
            ->toBe(sprintf('refused: %s', AStackThatKeepsWakes::NOT_HTTPS), $which);
    }
});

it('says a stack that is not answering is what stood in the way of registering and of removing', function (): void {
    foreach (everyWayOfRegisteringAWake(AStackThatKeepsWakes::unreachable(), MockResponse::make('{"error":"gone"}', 500), MockResponse::make('{"error":"gone"}', 500)) as $which => $make) {
        $keeping = $make();

        expect(whatRegisteringCameTo($keeping->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
            ->toBe(KindOfObstacle::StackDidNotAnswer->value, $which)
            ->and(whatRemovingCameTo($keeping->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME))))
            ->toBe(KindOfObstacle::StackDidNotAnswer->value, $which);
    }
});

it('removes the endpoint by its name, and reads one the stack holds no longer as removed', function (): void {
    $ways = everyWayOfRegisteringAWake(
        AStackThatKeepsWakes::reachable()->alreadyHolds(aStackThatWakesAPhone(), THE_PHONES_PUSH_NAME, THE_PHONES_ENDPOINT),
        MockResponse::make(whatAStackSaysOfARemovedWake()),
        MockResponse::make(aWakeRefusedWith('PUSH-5', 'No push endpoint is registered under that name.'), 404),
    );

    foreach ($ways as $which => $make) {
        $keeping = $make();

        expect(whatRemovingCameTo($keeping->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME))))->toBe('removed', $which)
            ->and(whatRemovingCameTo($keeping->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME))))->toBe('removed', $which);
    }
});

// Beyond the contract: what the adapter sends, and what it refuses to read.

it('registers with a POST carrying the name, the endpoint and both keys in base64url, and removes with a DELETE naming it', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make(whatAStackSaysOfARegisteredWake()), MockResponse::make(whatAStackSaysOfARemovedWake())]);
    $keeping = new PushEndpointKeepers(new PinnedClients());

    $keeping->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister());
    $registered = $mock->getLastPendingRequest();
    $keeping->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME));
    $removed = $mock->getLastPendingRequest();

    expect(parse_url((string) $registered?->getUrl(), PHP_URL_PATH))->toBe('/api/push-endpoints')
        ->and($registered?->getMethod())->toBe(Method::POST)
        ->and($registered?->body()?->all())->toBe([
            'name' => THE_PHONES_PUSH_NAME,
            'endpoint' => THE_PHONES_ENDPOINT,
            'p256dh' => 'BAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQE',
            'auth' => 'c3Nzc3Nzc3Nzc3Nzc3Nzcw',
        ])
        ->and(parse_url((string) $removed?->getUrl(), PHP_URL_PATH))->toBe(sprintf('/api/push-endpoints/%s', THE_PHONES_PUSH_NAME))
        ->and($removed?->getMethod())->toBe(Method::DELETE);
});

it('cannot read an answer that names no fingerprint as the one registered or removed', function (): void {
    foreach ([
        [MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoint', 'data' => 'registered']), MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoints', 'data' => 'removed'])],
        [MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoint', 'data' => ['name' => THE_PHONES_PUSH_NAME, 'fingerprint' => ' ', 'host' => 'ntfy.sh', 'registered' => 1_793_872_800, 'rehearsed' => false]]), MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoints', 'data' => ['endpoints' => [], 'rehearsed' => false, 'removed' => null]])],
    ] as [$registering, $removing]) {
        MockClient::destroyGlobal();
        MockClient::global([$registering, $removing]);
        $keeping = new PushEndpointKeepers(new PinnedClients());

        expect(whatRegisteringCameTo($keeping->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
            ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value)
            ->and(whatRemovingCameTo($keeping->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME))))
            ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
    }
});

it('cannot read an answer naming no endpoint name as the one registered', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoint', 'data' => ['fingerprint' => THE_PHONES_FINGERPRINT, 'host' => 'ntfy.sh', 'name' => ' ', 'registered' => 1_793_872_800, 'rehearsed' => false]])]);

    expect(whatRegisteringCameTo(new PushEndpointKeepers(new PinnedClients())->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
});

it('cannot read an answer naming the endpoint by a fingerprint that is not sixteen hexadecimal digits, or not the address this phone sent', function (string $fingerprint): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(['api_version' => 1, 'kind' => 'push-endpoint', 'data' => ['fingerprint' => $fingerprint, 'host' => 'ntfy.sh', 'name' => THE_PHONES_PUSH_NAME, 'registered' => 1_793_872_800, 'rehearsed' => false]])]);

    expect(whatRegisteringCameTo(new PushEndpointKeepers(new PinnedClients())->registeredOn(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), theWakeToRegister())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'another address' => ['0f4e2a91c7b35d68'],
    'upper case' => [strtoupper(THE_PHONES_FINGERPRINT)],
    'one digit short' => [substr(THE_PHONES_FINGERPRINT, 0, -1)],
    'the whole address' => [THE_PHONES_ENDPOINT],
]);

it('says a removal the stack refused for another reason in its words', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(aWakeRefusedWith('PUSH-1', 'That word cannot name a push endpoint.'), 400)]);

    expect(whatRemovingCameTo(new PushEndpointKeepers(new PinnedClients())->removedFrom(aStackThatWakesAPhone(), Session::of('a-session-not-a-secret'), APushName::of(THE_PHONES_PUSH_NAME))))
        ->toBe('refused: That word cannot name a push endpoint.');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('PushEndpointEnvelope', whatAStackSaysOfARegisteredWake()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('PushEndpointsEnvelope', whatAStackSaysOfARemovedWake()))
        ->toBe([]);
});
