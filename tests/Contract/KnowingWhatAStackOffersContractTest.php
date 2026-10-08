<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Api\Assessors;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\WhatTheContractAccepts;

// The KnowingWhatAStackOffers contract, run against the adapter and its fake.
//
// What a stack says it offers, asked for one action at a time: each answer as
// itself, each stack for itself, and a stack that could not be asked keeping
// its actions offered.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A stack whose offers are asked about, at an address of its own. Named for this file. */
function aStackWhoseOffersAreAsked(string $seed, string $at): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of(sprintf('Offering %s', $seed)),
        Address::of($at),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

function theLoftThatOffers(): Stack
{
    return aStackWhoseOffersAreAsked('l', 'https://192.168.1.42:8443');
}

function theShedThatOffers(): Stack
{
    return aStackWhoseOffersAreAsked('s', 'https://10.0.0.7:8443');
}

/**
 * What the loft declares: a restart it offers, a copy it has not set up and a
 * removal this account may not ask for. Nothing else.
 *
 * @return array<string, mixed>
 */
function whatTheLoftDeclares(): array
{
    return ['api_version' => 1, 'kind' => 'capabilities', 'data' => ['capabilities' => [
        Api::action(WhatToDoWithIt::Restart->asked()) => 'available',
        Api::action(WhatToDoWithACopy::Take->asked()) => 'unconfigured',
        Api::action(TakingThemOut::TakeThemOut->asked()) => 'unpermitted',
    ]]];
}

/**
 * Both ways of knowing what a stack offers, each arranged with the loft
 * declaring what it declares, the shed declaring nothing, and every other
 * stack out of reach.
 *
 * @return array<string, Closure(): KnowingWhatAStackOffers>
 */
function everyWayOfKnowingWhatAStackOffers(): array
{
    return [
        'the fake' => fn(): KnowingWhatAStackOffers => AStackThatOffers::onTheStack(theLoftThatOffers()->id(), WhetherItIsOffered::NeedsANewerLemonfiber, [
            WhatToDoWithIt::Restart->asked() => WhetherItIsOffered::Offered,
            WhatToDoWithACopy::Take->asked() => WhetherItIsOffered::NotSetUp,
            TakingThemOut::TakeThemOut->asked() => WhetherItIsOffered::NotTheirs,
        ])->andOnTheStack(theShedThatOffers()->id(), WhetherItIsOffered::NeedsANewerLemonfiber),
        'the adapter' => function (): KnowingWhatAStackOffers {
            MockClient::destroyGlobal();
            MockClient::global(['*' => static fn(PendingRequest $asked): MockResponse => match (true) {
                str_starts_with($asked->getUrl(), theLoftThatOffers()->at()->forTheClient()) => MockResponse::make((string) json_encode(whatTheLoftDeclares())),
                str_starts_with($asked->getUrl(), theShedThatOffers()->at()->forTheClient()) => MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'capabilities', 'data' => ['capabilities' => []]])),
                default => MockResponse::make()->throw(static fn(PendingRequest $request): Unreachable => Unreachable::whenAsking($request->getRequest()->resolveEndpoint(), 'Connection refused')),
            }]);

            return new Assessors(new PinnedClients(), FrozenClock::at(Instant::atEpochSeconds(0)), new WhatEachStackOffers());
        },
    ];
}

/**
 * What one way of knowing says of an action, on the frame after the one that asked where it had to ask.
 *
 * The adapter asks a stack it holds nothing for on a frame of its own and
 * answers on the next; the fake holds every answer from the start. Asking
 * twice where the first waited is what a screen does.
 */
function whatIsSaidOf(KnowingWhatAStackOffers $knowing, Stack $stack, AnAction $action): WhetherItIsOffered
{
    try {
        return $knowing->whetherItOffers($stack, Session::of('a-session-not-a-secret'), $action);
    } catch (TheReadingWaitsAFrame) {
        return $knowing->whetherItOffers($stack, Session::of('a-session-not-a-secret'), $action);
    }
}

dataset('every way of knowing what a stack offers', static fn(): array => array_map(
    static fn(Closure $arranged): array => [$arranged],
    everyWayOfKnowingWhatAStackOffers(),
));

it('says each thing a stack declares as itself, and what it does not declare as needing a newer lemonfiber', function (KnowingWhatAStackOffers $knowing): void {
    expect(whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered)
        ->and(whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithACopy::Take))->toBe(WhetherItIsOffered::NotSetUp)
        ->and(whatIsSaidOf($knowing, theLoftThatOffers(), TakingThemOut::TakeThemOut))->toBe(WhetherItIsOffered::NotTheirs)
        ->and(whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Stop))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber);
})->with('every way of knowing what a stack offers');

it('answers each stack for itself, never with what another said', function (KnowingWhatAStackOffers $knowing): void {
    expect(whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered)
        ->and(whatIsSaidOf($knowing, theShedThatOffers(), WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber);
})->with('every way of knowing what a stack offers');

it('keeps the actions of a stack it could not ask offered', function (KnowingWhatAStackOffers $knowing): void {
    expect(whatIsSaidOf($knowing, aStackWhoseOffersAreAsked('q', 'https://10.0.0.9:8443'), WhatToDoWithIt::Restart))
        ->toBe(WhetherItIsOffered::NotKnown)
        ->and(WhetherItIsOffered::NotKnown->offersAnAction())->toBeTrue();
})->with('every way of knowing what a stack offers');

it('lets go of what a stack said when asked to ask again, and of nothing for a stack never asked', function (KnowingWhatAStackOffers $knowing): void {
    whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Restart);

    expect($knowing->askAgain(theLoftThatOffers()->id())->howMany())->toBe(1)
        ->and($knowing->askAgain(aStackWhoseOffersAreAsked('q', 'https://10.0.0.9:8443')->id())->howMany())->toBe(0);
})->with('every way of knowing what a stack offers');

it('lets go of nothing a stack has just said when a screen opens', function (KnowingWhatAStackOffers $knowing): void {
    whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Restart);

    expect($knowing->aScreenOpens()->howMany())->toBe(0)
        ->and(whatIsSaidOf($knowing, theLoftThatOffers(), WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered);
})->with('every way of knowing what a stack offers');

it('stands the adapter\'s stacks in with a declaration the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', whatTheLoftDeclares()))->toBe([]);
});
