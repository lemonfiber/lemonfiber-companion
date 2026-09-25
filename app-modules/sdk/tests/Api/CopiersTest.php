<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\Copiers;
use Modules\Sdk\Api\PinnedClients;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

/**
 * What the adapter puts on the wire, which the contract cannot ask about: a
 * fake dials nothing, so the path, the argument and the key are checked here.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a copy is asked of. */
function theStackTheCopierAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a copy it took on with.
 *
 * @return array<string, mixed>
 */
function whatAStackTakingACopySends(): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'backup', 'job' => 'a-copy']];
}

/** The request asking for that copy sent. */
function whatAskingForACopySent(ACopyAsked $asked): PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackTakingACopySends()), 202)]);

    new Copiers(new PinnedClients(), SequencedEntropy::counting())->take(theStackTheCopierAsks(), Session::of('a-session-not-a-secret'), $asked);

    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks for a copy of the whole stack at the backup action, naming no service', function (): void {
    $sent = whatAskingForACopySent(ACopyAsked::ofTheWholeStack());

    expect($sent->getUrl())->toEndWith('/api/actions/backup')
        ->and($sent->body()?->all())->toBe([]);
});

it('asks for a copy of one service by naming it under `service`', function (): void {
    $sent = whatAskingForACopySent(ACopyAsked::ofOneService(ServiceId::called('sonarr')));

    expect($sent->getUrl())->toEndWith('/api/actions/backup')
        ->and($sent->body()?->all())->toBe(['service' => 'sonarr']);
});

it('names the attempt in the header, because asking for a copy changes a stack', function (): void {
    expect(whatAskingForACopySent(ACopyAsked::ofTheWholeStack())->headers()->get(Api::IDEMPOTENCY_HEADER))
        ->toBe(SequencedEntropy::counting()->nonce()->shown());
});

it('asks after a copy at the handle it was answered with', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404)]);

    new Copiers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(theStackTheCopierAsks(), Session::of('a-session-not-a-secret'), Job::named('a-copy'));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/jobs/a-copy');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', whatAStackTakingACopySends()))->toBe([]);
});
