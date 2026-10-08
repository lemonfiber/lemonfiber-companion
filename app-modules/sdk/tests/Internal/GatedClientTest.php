<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function afterEach;

use ArrayObject;

use function expect;
use function is_string;
use function it;
use function json_encode;

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\RestartAction;
use Lemonfiber\Sdk\Logs;
use Lemonfiber\Sdk\Repair;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\TheStackDoesNotOfferIt;
use Modules\Sdk\Internal\GatedClient;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function sprintf;
use function str_repeat;

use Tests\Support\WhatTheContractAccepts;
use Throwable;

/**
 * Which requests the gated client asks the stack about, and which it sends without asking.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * What a stack answers when it takes work on.
 *
 * @return array<string, mixed>
 */
function aStackTakingWorkOn(): array
{
    return ['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'restart', 'job' => 'a-job']];
}

/** The stack the gated client is for. */
function theStackTheGateIsFor(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * Clients writing down each path a request was asked through the gate for, and each client handed out without one.
 *
 * @param ArrayObject<int, string> $asked
 */
function clientsWritingDownWhatIsAsked(ArrayObject $asked): Clients
{
    return new readonly class ($asked) implements Clients {
        /** @param ArrayObject<int, string> $asked */
        public function __construct(private ArrayObject $asked) {}

        public function client(Stack $stack, Session $session): Client
        {
            $this->asked[] = 'without asking';

            return new PinnedClients()->client($stack, $session);
        }

        public function towards(Stack $stack, Session $session, Ability $path): Client
        {
            $this->asked[] = $path->named();

            return new PinnedClients()->client($stack, $session);
        }

        public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
        {
            return new PinnedClients()->whatStoodInTheWay($stack, $why);
        }
    };
}

/**
 * What one call on the gated client asked, whatever the stack answered.
 *
 * @return list<string>
 */
function whatACallAskedTheGate(string $call): array
{
    MockClient::destroyGlobal();
    MockClient::global(['*' => MockResponse::make((string) json_encode(aStackTakingWorkOn()), 202)]);
    $asked = new ArrayObject();
    $client = GatedClient::of(clientsWritingDownWhatIsAsked($asked), theStackTheGateIsFor(), Session::of('a-session-not-a-secret'));

    try {
        $answered = match ($call) {
            'read' => $client->read(Api::STATUS_ENDPOINT),
            'act' => $client->act(new RestartAction(services: ['sonarr']), 'a-key'),
            'repair' => $client->repair(Repair::offer()),
            'logs' => $client->logs(Logs::ofService('sonarr', 3)),
            'follow' => $client->whatBecameOf('a-job'),
            'let go' => $client->letGoOf('a-job'),
            'bundle' => $client->bundle('a-bundle'),
            default => sprintf('%s is no call on the gated client', $call),
        };
    } catch (ApiVersionMismatch|ConfigurationProblem|NoSuchJob|RequestFailed|TheStackDoesNotOfferIt|UnexpectedKind|Unreachable|UnreadableResponse) {
        // What the stack answered is not the question here; what was asked is.
        $answered = null;
    }

    return is_string($answered) ? [$answered] : $asked->getArrayCopy();
}

it('asks the stack about a reading by the path it is read at', function (): void {
    expect(whatACallAskedTheGate('read'))->toBe([Api::STATUS_ENDPOINT]);
});

it('asks the stack about an action by the path it is asked at', function (): void {
    expect(whatACallAskedTheGate('act'))->toBe([Api::action(WhatToDoWithIt::Restart->asked())]);
});

it('asks the stack about a repair by the path the repair is asked at', function (): void {
    expect(whatACallAskedTheGate('repair'))->toBe([Repair::offer()->request()->endpoint()]);
});

it('asks the stack about a scrollback by the path logs are read at', function (): void {
    expect(whatACallAskedTheGate('logs'))->toBe([Api::LOGS_ENDPOINT]);
});

it('follows work, lets it go and fetches a bundle without asking, none being a request a stack declares', function (): void {
    expect(whatACallAskedTheGate('follow'))->toBe(['without asking'])
        ->and(whatACallAskedTheGate('let go'))->toBe(['without asking'])
        ->and(whatACallAskedTheGate('bundle'))->toBe(['without asking']);
});

it('stands in for a stack with an answer the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('JobEnvelope', aStackTakingWorkOn()))->toBe([]);
});
