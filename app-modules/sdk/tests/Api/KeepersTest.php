<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\Keepers;
use Modules\Sdk\Api\PinnedClients;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter puts on the wire when it hands a command over.
 *
 * `HostingContractTest` runs both implementations against the same assertions
 * and a fake dials nothing, so the path, the argument and the key an act
 * travels under are checked here.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack these are asked of. */
function theStackAskedToKeepThings(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * The payload a machine answers an act with.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given.
 *
 * @return array<string, mixed>
 */
function whatAMachineThatDidItSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'hosting',
        'data' => [
            'manager' => 'launchd',
            'commands' => [[
                'name' => 'watch',
                'command' => 'lemonfiber watch',
                'guarantees' => 'stops the stack if the data location disappears',
                'standing' => 'hosted',
            ]],
            'changed' => ['name' => 'watch', 'installed' => true, 'started' => true, 'rehearsed' => false, 'touched' => []],
        ],
    ];
}

/** What a machine answers an act with. */
function aMachineThatDidIt(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAMachineThatDidItSends()));
}

/**
 * The requests one adapter sent, handing over each of these in turn.
 *
 * One {@see Keepers} for all of them, for `SupervisorsTest`'s reason: a fresh
 * one per attempt would make every key different by construction.
 *
 * @return list<PendingRequest>
 */
function whatWasSentHandingOver(HostingAgreed ...$agreed): array
{
    $answers = [];

    foreach ($agreed as $ignored) {
        $answers[] = aMachineThatDidIt();
    }

    MockClient::destroyGlobal();
    $mock = MockClient::global($answers);
    $keepers = new Keepers(new PinnedClients(), SequencedEntropy::counting());
    $sent = [];

    foreach ($agreed as $one) {
        $keepers->handOver(theStackAskedToKeepThings(), Session::of('a-session-not-a-secret'), $one);

        $request = $mock->getLastPendingRequest();

        // Raised rather than expected, for `SupervisorsTest`'s reason: a case
        // about a request nobody sent would pass on an adapter that stopped
        // sending one.
        if (! $request instanceof PendingRequest) {
            throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
        }

        $sent[] = $request;
    }

    return $sent;
}

it('asks for an install and a removal at the paths the SDK composes, naming the command under `kept`', function (): void {
    [$install, $remove] = whatWasSentHandingOver(
        HostingAgreed::to(HandingOver::Install, 'watch'),
        HostingAgreed::to(HandingOver::Remove, 'boot'),
    );

    expect($install->getUrl())->toEndWith('/api/actions/hosting-install')
        ->and($install->body()?->all())->toBe(['kept' => 'watch'])
        ->and($remove->getUrl())->toEndWith('/api/actions/hosting-remove')
        ->and($remove->body()?->all())->toBe(['kept' => 'boot']);
});

it('names each attempt in the header, afresh, and never in what the action takes', function (): void {
    // The first key is what `Entropy` hands out first, so this says where the
    // name came from; the second is a second name, so a retry by the operator
    // is a new attempt rather than a replay.
    [$first, $second] = whatWasSentHandingOver(
        HostingAgreed::to(HandingOver::Install, 'watch'),
        HostingAgreed::to(HandingOver::Install, 'watch'),
    );

    expect($first->headers()->get(Api::IDEMPOTENCY_HEADER))->toBe(SequencedEntropy::counting()->nonce()->shown())
        ->and($second->headers()->get(Api::IDEMPOTENCY_HEADER))->not->toBe($first->headers()->get(Api::IDEMPOTENCY_HEADER))
        ->and($first->body()?->all())->toBe(['kept' => 'watch']);
});

it('stands in for a machine with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('HostingEnvelope', whatAMachineThatDidItSends()))
        ->toBe([], "The payload this suite stands in for a machine with is not one a stack would send.\n");
});
