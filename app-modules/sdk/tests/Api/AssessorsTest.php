<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;

use ArrayObject;
use Closure;

use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Api\Assessors;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;
use function str_starts_with;

use Tests\Support\Fakes\FrozenClock;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter makes of each way asking a stack what it serves can end.
 *
 * `KnowingWhatAStackOffersContractTest` holds the adapter and its fake to the
 * same answers; this holds what only the adapter does: how long an answer is
 * held, what lets go of it, and which session it is held for.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The moment these cases begin. */
const THE_FIRST_LOOK = 1_790_000_000;

/** A stack at an address of its own, so two of them are told apart on the wire. */
function aStackThatIsAssessed(string $nonce = 'a', string $at = 'https://192.168.1.42:8443'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($nonce, Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of($at),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The session these are asked with. */
function theSessionAssessedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * Every stack answering what it serves with this, each request written down.
 *
 * @param Closure(PendingRequest): MockResponse $answering
 *
 * @return ArrayObject<int, string> the paths asked, in order
 */
function everyStackAnswering(Closure $answering): ArrayObject
{
    /** @var ArrayObject<int, string> $asked */
    $asked = new ArrayObject();

    MockClient::destroyGlobal();
    MockClient::global(['*' => static function (PendingRequest $request) use ($answering, $asked): MockResponse {
        $asked[] = $request->getUrl();

        return $answering($request);
    }]);

    return $asked;
}

/**
 * A declaration, as a stack writes one.
 *
 * @param array<string, string> $capabilities
 *
 * @return array<string, mixed>
 */
function whatAStackServesSaying(array $capabilities): array
{
    return ['api_version' => 1, 'kind' => 'capabilities', 'data' => ['scope' => 'operator', 'capabilities' => $capabilities]];
}

/**
 * A declaration, as a stack answers with one.
 *
 * @param array<string, string> $capabilities
 */
function aDeclarationOf(array $capabilities): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackServesSaying($capabilities)));
}

/**
 * The refusal of a path a stack has no endpoint at.
 *
 * @return array<string, mixed>
 */
function aRefusalOfAPathItDoesNotServe(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'ASK-9',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'There is nothing at that path',
        'meaning' => 'This lemonfiber serves no request there.',
        'remedies' => [['action' => 'Check the path against the API this lemonfiber serves']],
    ]];
}

/** A stack older than the declaration, refusing the path as one it has no endpoint at. */
function noEndpointThere(): MockResponse
{
    return MockResponse::make((string) json_encode(aRefusalOfAPathItDoesNotServe()), 404);
}

/** An update a reading offered, which is all taking one needs to be asked about. */
function anUpdateToTake(): TakingAnUpdate
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

/** The adapter, over a clock that can be moved. */
function anAssessor(FrozenClock $clock, ?WhatEachStackOffers $held = null): Assessors
{
    return new Assessors(new PinnedClients(), $clock, $held ?? new WhatEachStackOffers());
}

function aClockAtTheFirstLook(): FrozenClock
{
    return FrozenClock::at(Instant::atEpochSeconds(THE_FIRST_LOOK));
}

/**
 * What the button for this action is drawn as: that it waits a frame, or what the stack says of it.
 */
function whatTheButtonSays(Assessors $assessor, AnAction $action, ?Stack $stack = null, ?Session $session = null): string
{
    try {
        return $assessor->whetherItOffers($stack ?? aStackThatIsAssessed(), $session ?? theSessionAssessedWith(), $action)->name;
    } catch (TheReadingWaitsAFrame) {
        return 'waits a frame';
    }
}

it('asks the stack what it serves on a frame of its own, and draws the button on the next', function (): void {
    $asked = everyStackAnswering(static fn(): MockResponse => aDeclarationOf([Api::action(WhatToDoWithIt::Restart->asked()) => 'available']));
    $assessor = anAssessor(aClockAtTheFirstLook());

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe('waits a frame')
        ->and($asked->count())->toBe(1)
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered->name)
        ->and($asked->count())->toBe(1);
});

it('waits a frame even for a button the stack does not serve, so nothing read after it is a second reading', function (): void {
    everyStackAnswering(static fn(): MockResponse => aDeclarationOf([]));
    $assessor = anAssessor(aClockAtTheFirstLook());

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe('waits a frame')
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber->name);
});

it('says what the stack declares of each action, absence included', function (): void {
    everyStackAnswering(static fn(): MockResponse => aDeclarationOf([
        Api::action(WhatToDoWithIt::Restart->asked()) => 'available',
        Api::action(WhatToDoWithACopy::Take->asked()) => 'unconfigured',
        Api::action(TakingThemOut::TakeThemOut->asked()) => 'unpermitted',
    ]));
    $assessor = anAssessor(aClockAtTheFirstLook());
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered->name)
        ->and(whatTheButtonSays($assessor, WhatToDoWithACopy::Take))->toBe(WhetherItIsOffered::NotSetUp->name)
        ->and(whatTheButtonSays($assessor, TakingThemOut::TakeThemOut))->toBe(WhetherItIsOffered::NotTheirs->name)
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Stop))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber->name);
});

it('reads a stack with no declaration as too old for everything but taking an update', function (): void {
    everyStackAnswering(static fn(): MockResponse => noEndpointThere());
    $assessor = anAssessor(aClockAtTheFirstLook());
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber->name)
        ->and(whatTheButtonSays($assessor, anUpdateToTake()))->toBe(WhetherItIsOffered::NotKnown->name);
});

it('offers everything of a stack it could not ask, and of one whose answer it could not read', function (MockResponse $answer): void {
    everyStackAnswering(static fn(): MockResponse => $answer);
    $assessor = anAssessor(aClockAtTheFirstLook());

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe('waits a frame')
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::NotKnown->name);
})->with([
    'a stack that did not answer' => [MockResponse::make()->throw(static fn(PendingRequest $asked): Unreachable
        => Unreachable::whenAsking($asked->getRequest()->resolveEndpoint(), 'Connection refused'))],
    'a refusal that is not about the path' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'error', 'data' => ['code' => 'AUTH-1', 'severity' => 'error', 'state' => 'guided', 'summary' => 'No']]), 401)],
    'a state nobody can read' => [aDeclarationOf(['/api/status' => 'perhaps'])],
    // `status` is stood in for and not judged: the case is an answer of the wrong kind, which is refused before anything in it is read.
    'an answer of another kind' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'status', 'data' => []]))],
    'an answer in another version' => [MockResponse::make((string) json_encode(['api_version' => 2, 'kind' => 'capabilities', 'data' => ['scope' => 'operator', 'capabilities' => []]]))],
    'nothing that reads as an envelope' => [MockResponse::make('not an envelope')],
]);

it('holds what a stack said while a screen is open, however long, and asks again where a screen opens after a break', function (): void {
    $asked = everyStackAnswering(static fn(): MockResponse => aDeclarationOf([Api::action(WhatToDoWithIt::Restart->asked()) => 'available']));
    $clock = aClockAtTheFirstLook();
    $assessor = anAssessor($clock);
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    $clock->moveTo(Instant::atEpochSeconds(THE_FIRST_LOOK + HowOftenAScreenLooks::WhileOpen->seconds() - 1));

    expect($assessor->aScreenOpens())->toEqual(Forgotten::nothing());

    $clock->moveTo(Instant::atEpochSeconds(THE_FIRST_LOOK + 10 * HowOftenAScreenLooks::WhileOpen->seconds()));

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered->name)
        ->and($asked->count())->toBe(1)
        ->and($assessor->aScreenOpens())->toEqual(Forgotten::rows(1))
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe('waits a frame')
        ->and($asked->count())->toBe(2);
});

it('asks a stack it could not ask again where a screen opens as soon as a broken stream would be opened again', function (): void {
    everyStackAnswering(static fn(): MockResponse => MockResponse::make('not an envelope'));
    $clock = aClockAtTheFirstLook();
    $assessor = anAssessor($clock);
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    $clock->moveTo(Instant::atEpochSeconds(THE_FIRST_LOOK + HowOftenAScreenLooks::AfterABreak->seconds() - 1));

    expect($assessor->aScreenOpens())->toEqual(Forgotten::nothing());

    $clock->moveTo(Instant::atEpochSeconds(THE_FIRST_LOOK + HowOftenAScreenLooks::AfterABreak->seconds()));

    expect($assessor->aScreenOpens())->toEqual(Forgotten::rows(1));
});

it('holds a stack too old to say for as long as one that said', function (): void {
    everyStackAnswering(static fn(): MockResponse => noEndpointThere());
    $clock = aClockAtTheFirstLook();
    $assessor = anAssessor($clock);
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    $clock->moveTo(Instant::atEpochSeconds(THE_FIRST_LOOK + HowOftenAScreenLooks::WhileOpen->seconds() - 1));

    expect($assessor->aScreenOpens())->toEqual(Forgotten::nothing());
});

it('asks again for another session, whose account may be another', function (): void {
    $asked = everyStackAnswering(static fn(): MockResponse => aDeclarationOf([]));
    $assessor = anAssessor(aClockAtTheFirstLook());
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart, session: Session::of('a-member-not-a-secret')))->toBe('waits a frame')
        ->and($asked->count())->toBe(2);
});

it('never answers one stack with what another said', function (): void {
    everyStackAnswering(static fn(PendingRequest $request): MockResponse => str_starts_with($request->getUrl(), 'https://192.168.1.42')
        ? aDeclarationOf([Api::action(WhatToDoWithIt::Restart->asked()) => 'available'])
        : aDeclarationOf([]));
    $assessor = anAssessor(aClockAtTheFirstLook());
    $shed = aStackThatIsAssessed('b', 'https://10.0.0.7:8443');
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart, $shed);

    expect(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe(WhetherItIsOffered::Offered->name)
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart, $shed))->toBe(WhetherItIsOffered::NeedsANewerLemonfiber->name);
});

it('asks the stack again once asked to, saying what was let go of', function (): void {
    $asked = everyStackAnswering(static fn(): MockResponse => aDeclarationOf([]));
    $assessor = anAssessor(aClockAtTheFirstLook());

    expect($assessor->askAgain(aStackThatIsAssessed()->id()))->toEqual(Forgotten::nothing());

    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    expect($assessor->askAgain(aStackThatIsAssessed()->id()))->toEqual(Forgotten::rows(1))
        ->and(whatTheButtonSays($assessor, WhatToDoWithIt::Restart))->toBe('waits a frame')
        ->and($asked->count())->toBe(2);
});

it('lets go of what a stack said when the stack is removed, and keeps nothing of it after', function (): void {
    everyStackAnswering(static fn(): MockResponse => aDeclarationOf([]));
    $assessor = anAssessor(aClockAtTheFirstLook());
    whatTheButtonSays($assessor, WhatToDoWithIt::Restart);

    expect($assessor->keepsAnythingOf(aStackThatIsAssessed()->id()))->toBeTrue()
        ->and($assessor->forgetTheStack(aStackThatIsAssessed()->id()))->toEqual(Forgotten::rows(1))
        ->and($assessor->keepsAnythingOf(aStackThatIsAssessed()->id()))->toBeFalse()
        ->and($assessor->forgetTheStack(aStackThatIsAssessed()->id()))->toEqual(Forgotten::nothing());
});

it('stands in for a stack with a declaration and a refusal the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', whatAStackServesSaying([Api::action(WhatToDoWithIt::Restart->asked()) => 'unconfigured'])))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aRefusalOfAPathItDoesNotServe()))->toBe([]);
});
