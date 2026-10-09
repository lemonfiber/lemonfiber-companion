<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\KeepingThePlace;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\WhatThePlaceCameTo;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PlaceKeepers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatKeepsThePlace;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The KeepingThePlace contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a member's place is told to. */
function aStackThatIsToldAPlace(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a place with, changed where a case says.
 *
 * @param  array<array-key, mixed> $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfAPlace(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'watched', 'data' => [
        'id' => 'a1',
        'position' => 61,
        'ended' => false,
        'rehearsed' => false,
        ...$changed,
    ]];
}

/**
 * Both ways of keeping a place, each set up to say the same.
 *
 * @return array<string, Closure(): KeepingThePlace>
 */
function everyWayOfKeepingThePlace(MockResponse $answered, WhatThePlaceCameTo $same): array
{
    return [
        'the fake' => static fn(): KeepingThePlace => $same->either(
            kept: static fn(): AStackThatKeepsThePlace => AStackThatKeepsThePlace::keeping(),
            refused: static fn(Obstacle $why): AStackThatKeepsThePlace => AStackThatKeepsThePlace::refusing($why),
        ),
        'the adapter' => static function () use ($answered): KeepingThePlace {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new PlaceKeepers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** What telling a place came to, as the place kept or the obstacle's kind. */
function whatThePlaceCameTo(KeepingThePlace $keeping, ThePlace $place): string
{
    return $keeping->keep(aStackThatIsToldAPlace(), Session::of('a-session-not-a-secret'), $place)->either(
        kept: static fn(ThePlace $kept): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            $kept->isTheEnd() ? 'kept %s at %d, the end' : 'kept %s at %d',
            $kept->holding()->named(),
            $kept->howFarIn()->seconds(),
        )),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * What the adapter sent for one place, as the endpoint and the body.
 *
 * @return array{string, mixed}
 */
function whatTellingThePlaceSent(ThePlace $place): array
{
    $sent = ['', null];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent = [$asked->getRequest()->resolveEndpoint(), $asked->body()?->all()];

            return MockResponse::make((string) json_encode(whatAStackSaysOfAPlace()));
        },
    ]);

    new PlaceKeepers(new PinnedClients(), SequencedEntropy::counting())->keep(aStackThatIsToldAPlace(), Session::of('a-session-not-a-secret'), $place);

    return $sent;
}

it('tells the stack the place and reads the place it kept', function (): void {
    $place = ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61));
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfAPlace()));

    foreach (everyWayOfKeepingThePlace($answered, WhatThePlaceCameTo::kept($place)) as $which => $build) {
        expect(whatThePlaceCameTo($build(), $place))->toBe('kept a1 at 61', $which);
    }
});

it('reads the end where the core kept the place as the end', function (): void {
    $place = ThePlace::atTheEndOf(HoldingId::called('a1'), HowFarIn::at(5_400));
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfAPlace(['position' => 5_400, 'ended' => true])));

    foreach (everyWayOfKeepingThePlace($answered, WhatThePlaceCameTo::kept($place)) as $which => $build) {
        expect(whatThePlaceCameTo($build(), $place))->toBe('kept a1 at 5400, the end', $which);
    }
});

it('says the session was refused where the core would not take it', function (): void {
    $answered = MockResponse::make('{"error":"no"}', 401);

    foreach (everyWayOfKeepingThePlace($answered, WhatThePlaceCameTo::refused(Obstacle::of(KindOfObstacle::CredentialWasRefused))) as $which => $build) {
        expect(whatThePlaceCameTo($build(), ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61))))->toBe(KindOfObstacle::CredentialWasRefused->name, $which);
    }
});

it('says the stack did not answer where the answer is not a place kept', function (array $answer): void {
    $answered = MockResponse::make((string) json_encode($answer));

    foreach (everyWayOfKeepingThePlace($answered, WhatThePlaceCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))) as $which => $build) {
        expect(whatThePlaceCameTo($build(), ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61))))->toBe(KindOfObstacle::StackDidNotAnswer->name, $which);
    }
})->with([
    'another kind' => [[...whatAStackSaysOfAPlace(), 'kind' => 'grant']],
    'no title' => [whatAStackSaysOfAPlace(['id' => ''])],
    'no position' => [whatAStackSaysOfAPlace(['position' => null])],
    'no word on the end' => [whatAStackSaysOfAPlace(['ended' => null])],
]);

it('tells the place by the core\'s id in whole seconds, and says nothing of finishing until the end', function (): void {
    expect(whatTellingThePlaceSent(ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61))))
        ->toBe(['/api/actions/watched', ['name' => null, 'id' => 'a1', 'position' => 61, 'ended' => null]])
        ->and(whatTellingThePlaceSent(ThePlace::atTheEndOf(HoldingId::called('a1'), HowFarIn::at(5_400))))
        ->toBe(['/api/actions/watched', ['name' => null, 'id' => 'a1', 'position' => 5_400, 'ended' => true]]);
});

it('stands in for a stack with an answer the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('WatchedEnvelope', whatAStackSaysOfAPlace()))->toBe([]);
});
