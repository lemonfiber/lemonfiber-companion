<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function is_string;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Undoing;
use Modules\Sdk\Api\Menders;
use Modules\Sdk\Api\PinnedClients;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the repairs adapter puts on the wire, which the contract cannot ask.
 *
 * `MendingContractTest` runs both implementations against the same assertions
 * and the fake dials nothing, so what a request carried has nowhere to be
 * checked there. It is checked here, beside {@see SupervisorsTest}, and the
 * half worth checking is which of these two calls names an attempt: the yes
 * changes somebody's disk and the question changes nothing.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose repairs are agreed to. */
function theMendingStack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The listing a yes is given against. */
function theListingAgreedTo(): Offer
{
    return Offer::of('agreement-a-test-can-name', Repairs::of(
        Repair::offered(
            'storage.one-filesystem',
            'Move the library onto the larger disk',
            Effects::of('Downloads pause while it moves'),
            Undoing::Possible,
        ),
    ));
}

/** The yes itself, quoting the listing it was read in. */
function theRepairAgreedTo(): Confirmed
{
    $offer = theListingAgreedTo();

    foreach ($offer->repairs() as $repair) {
        return Confirmed::against($repair, $offer, Reading::live($offer));
    }

    throw new RuntimeException('theListingAgreedTo() holds one repair, so this is unreachable.');
}

/**
 * The payload a stack sends when it takes a repair on.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * @return array<string, mixed>
 */
function whatAStackTakingARepairOnSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'repair', 'job' => 'a-job'],
    ];
}

/** What a stack answers when it takes a repair on. */
function aRepairTakenOn(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackTakingARepairOnSends()), 202);
}

/**
 * The key one agreement travelled under.
 *
 * Its own copy rather than {@see SupervisorsTest}'s, since the suite runs in
 * parallel and a process given one of these files does not load the other.
 *
 * Raised rather than expected where there is none: a case comparing two absent
 * keys would find them equal and report that nothing had been reused.
 */
function theKeyNaming(?PendingRequest $sent): string
{
    $key = $sent?->headers()->get(Api::IDEMPOTENCY_HEADER);

    if (! is_string($key) || $key === '') {
        throw new RuntimeException('That agreement named no attempt, so this case read nothing.');
    }

    return $key;
}

/**
 * The key each of several agreements travelled under, through one adapter.
 *
 * One {@see Menders} for all of them, for {@see theKeysOfAttemptsAnswered()}'s
 * reason: the object a screen holds is bound once and agreed to many times.
 *
 * @param list<MockResponse> $answers
 *
 * @return list<string>
 */
function theKeysOfAgreementsAnswered(array $answers): array
{
    MockClient::destroyGlobal();
    $mock = MockClient::global($answers);

    $menders = new Menders(new PinnedClients(), SequencedEntropy::counting());
    $keys = [];

    foreach ($answers as $ignored) {
        $menders->agreeTo(theMendingStack(), Session::of('a-session-not-a-secret'), theRepairAgreedTo());

        $keys[] = theKeyNaming($mock->getLastPendingRequest());
    }

    return $keys;
}

it('N1-R42 — an agreement names the attempt it is part of', function (): void {
    [$key] = theKeysOfAgreementsAnswered([aRepairTakenOn()]);

    expect($key)->not->toBe('');
});

it('N1-R42 — a second agreement is a second name', function (): void {
    // The same shape a verb is held to. A repair reaches the services and
    // changes an operator's disk, so carrying one out twice under one name is
    // the most expensive way this requirement can be broken.
    [$first, $second] = theKeysOfAgreementsAnswered([aRepairTakenOn(), aRepairTakenOn()]);

    expect($second)->not->toBe($first);
});

it('N1-R42 — asking what would be put right names no attempt, since it changes nothing', function (): void {
    // The line the requirement itself draws: a key belongs on an action that
    // changes a stack. `Repair::offer()` describes what would be done and
    // carries out none of it, and a key on a question invites a stack to one
    // day answer a fresh reading with an old one.
    MockClient::destroyGlobal();
    $mock = MockClient::global([aRepairTakenOn()]);

    new Menders(new PinnedClients(), SequencedEntropy::counting())
        ->wouldPutRight(theMendingStack(), Session::of('a-session-not-a-secret'));

    expect($mock->getLastPendingRequest()?->headers()->get(Api::IDEMPOTENCY_HEADER))->toBeNull();
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', whatAStackTakingARepairOnSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
