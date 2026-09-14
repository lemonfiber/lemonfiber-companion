<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function is_array;
use function is_string;
use function it;
use function json_encode;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Upkeepers;
use Modules\Sdk\Api\WireField;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter puts on the wire, and what it does with an answer it cannot use.
 *
 * `KeepingCurrentContractTest` runs both implementations against the same
 * assertions, and a fake dials nothing — so the path, the argument, and every
 * way a real conversation can fail have nowhere to be checked there.
 *
 * **The argument matters as much as the path.** This endpoint serves two
 * readings and answers a request naming neither in prose rather than with an
 * envelope, so a call that forgot to say which one it wants fails as *the stack
 * did not answer* — which is true and useless, and points at the machine
 * instead of at the request.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose upkeep these are asked of. */
function theStackWhoseUpkeepTheAdapterAsksAfter(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The adapter, answering with whatever a case says. */
function theUpkeepAdapterAnswering(MockResponse ...$answers): Upkeepers
{
    MockClient::destroyGlobal();
    MockClient::global($answers);

    return new Upkeepers(new PinnedClients());
}

/** What the reading turned out to be, as a word a case can compare. */
function whatTheAdapterMade(MockResponse $answer): string
{
    return theUpkeepAdapterAnswering($answer)->standing(
        theStackWhoseUpkeepTheAdapterAsksAfter(),
        Session::of('a-session-not-a-secret'),
    )->either(
        // A reading that came through is not what any case here is about, and
        // is named so that one meaning to assert an obstacle cannot pass by
        // meeting a payload it could read.
        stands: static fn(): WhatTheTakingTurnedOutToBe => new WhatTheTakingTurnedOutToBe('a reading'),
        met: static fn(Obstacle $why): WhatTheTakingTurnedOutToBe
            => new WhatTheTakingTurnedOutToBe($why->name),
    )->said;
}

/** The same for the verb, which answers a different type. */
function whatTakingItMade(MockResponse $answer): Underway
{
    return theUpkeepAdapterAnswering($answer)->take(
        theStackWhoseUpkeepTheAdapterAsksAfter(),
        Session::of('a-session-not-a-secret'),
        TakingAnUpdate::agreed(
            Release::called('4.1.0', noticeable: true, withdrawn: false),
            Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        ),
    );
}

/**
 * The payload a stack sends where it took an update on.
 *
 * Both fields, because the contract requires both. A `job` payload short of
 * its `action` is a sample of an acknowledgement no stack sends, and a reader
 * tested only against it has been tested against nothing.
 *
 * @return array<string, mixed>
 */
function whatAStackTakingAnUpdateSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'update', 'job' => 'an-update'],
    ];
}

/** What the far end would say to a taking. */
function aTakingWasStarted(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackTakingAnUpdateSends()));
}

/**
 * The request the adapter actually sent, asking after upkeep.
 *
 * The mock is made here and held, rather than through the helper above: that
 * one replaces the global client, so a handle taken before it is a handle to a
 * client nothing dialled.
 */
function whatWasSentAskingAfterUpkeep(): PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Upkeepers(new PinnedClients())->standing(
        theStackWhoseUpkeepTheAdapterAsksAfter(),
        Session::of('a-session-not-a-secret'),
    );

    $sent = $mock->getLastPendingRequest();

    // Raised rather than expected, for the reason {@see whatWasSentSaying()}
    // gives: a case that asserted about a request nobody sent would pass on an
    // adapter that had stopped sending one.
    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('says which of the two readings it wants', function (): void {
    // The endpoint serves what is running and where this copy of lemonfiber
    // stands. A request naming neither is answered in prose, so this is the
    // difference between a screen and an obstacle.
    $sent = whatWasSentAskingAfterUpkeep()->query()->all();

    expect($sent)->toHaveKey('what')
        ->and($sent['what'])->toBe('stack');
});

it('N1-R10 — tells a credential that was refused from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        expect(whatTheAdapterMade($answered))->toBe($why->name);
    }
});

it('N2-R14 — a payload this side cannot read is an obstacle, not an exception', function (): void {
    // The case the fake cannot be asked about, because it has no payload to be
    // short of. An operator meets the same thing either way: the stack said
    // something, and this app cannot act on it.
    // Payloads no stack would send, which is the whole of what this asks: each
    // is a different way for the far end to answer with something this side
    // cannot act on, and the operator meets the same thing every time.
    $unreadable = [
        'not an envelope at all',
        (string) json_encode(anEnvelopeOfTheWrongKind()),
        (string) json_encode(anUpdateNobodyCanRead()),
    ];

    foreach ($unreadable as $said) {
        expect(whatTheAdapterMade(MockResponse::make($said)))->toBe(Obstacle::StackDidNotAnswer->name);
    }
});

it('N2-R17 — sends the services that were agreed to, by name', function (): void {
    // The other half of the confirmation. `N2-R17` has the question name the
    // services it would change, and a request that named none of them — or the
    // wrong ones — would have the operator agree to one evening and the stack
    // carry out another.
    MockClient::destroyGlobal();
    $mock = MockClient::global([aTakingWasStarted()]);

    new Upkeepers(new PinnedClients())->take(
        theStackWhoseUpkeepTheAdapterAsksAfter(),
        Session::of('a-session-not-a-secret'),
        TakingAnUpdate::agreed(
            Release::called('4.1.0', noticeable: true, withdrawn: false),
            Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        ),
    );

    expect(whatWasSentAgreeing($mock))->toBe(['jellyfin', 'sonarr']);
});

it('N2-R17 — carries the agreement through to the job the stack started', function (): void {
    // The started arm, named by the job the stack handed back, so a case
    // meaning to assert an evening is under way cannot pass by meeting an
    // obstacle whose name happens to match.
    expect(whatBecameOfTheTaking(whatTakingItMade(aTakingWasStarted())))->toBe('an-update');
});

it('N1-R10 — a refused taking is told apart from a stack that did not answer', function (): void {
    // The verb's own arms. They are separate code from the reading's, and a
    // refusal reaching an operator under the wrong headline sends them to reset
    // a credential that is working.
    $refused = whatTakingItMade(MockResponse::make('{"error":"no"}', 401));
    $silent = whatTakingItMade(MockResponse::make('{"error":"gone"}', 500));

    expect(whatBecameOfTheTaking($refused))->toBe(Obstacle::CredentialWasRefused->name)
        ->and(whatBecameOfTheTaking($silent))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('N2-R14 — an acknowledgement this side cannot read is an obstacle too', function (): void {
    // The stack started something and this app cannot say what. Reported as an
    // obstacle rather than thrown, because the operator's question is whether
    // the evening is under way and the honest answer is that it is not known.
    $unreadable = whatTakingItMade(MockResponse::make(
        (string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => []]),
    ));

    expect(whatBecameOfTheTaking($unreadable))->toBe(Obstacle::StackDidNotAnswer->name);
});

/**
 * The services a taking actually named on the wire.
 *
 * @return list<string>
 */
function whatWasSentAgreeing(MockClient $mock): array
{
    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    $body = $sent->body()?->all();
    $named = is_array($body) ? ($body[WireField::Services->value] ?? []) : [];
    $said = [];

    foreach (is_array($named) ? $named : [] as $service) {
        $said[] = is_string($service) ? $service : '';
    }

    return $said;
}

/**
 * An envelope answering about something else entirely.
 *
 * @return array<string, mixed>
 */
function anEnvelopeOfTheWrongKind(): array
{
    return ['api_version' => 1, 'kind' => 'status', 'data' => []];
}

/**
 * An `update` envelope whose state is a word this side has no case for.
 *
 * @return array<string, mixed>
 */
function anUpdateNobodyCanRead(): array
{
    return ['api_version' => 1, 'kind' => 'update', 'data' => ['state' => 'nearly']];
}

/** One answer carried out of an `either()` arm, which hands back objects. */
final readonly class WhatTheTakingTurnedOutToBe
{
    public function __construct(public string $said) {}
}

/** What became of a taking, as a word a case can compare. */
function whatBecameOfTheTaking(Underway $underway): string
{
    return $underway->either(
        started: static fn(Job $job): WhatTheTakingTurnedOutToBe
            => new WhatTheTakingTurnedOutToBe($job->shown()),
        met: static fn(Obstacle $why): WhatTheTakingTurnedOutToBe
            => new WhatTheTakingTurnedOutToBe($why->name),
    )->said;
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    // Only the acknowledgement. The two above it are payloads a stack cannot
    // send, deliberately — they are what this suite exists to watch the adapter
    // refuse, and holding them to the contract would be asking them to stop
    // being the thing under test.
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', whatAStackTakingAnUpdateSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
