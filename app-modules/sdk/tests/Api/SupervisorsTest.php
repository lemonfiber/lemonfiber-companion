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
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Supervisors;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;

/**
 * What the adapter puts on the wire, which the contract cannot ask about.
 *
 * `SupervisingContractTest` runs both implementations against the same
 * assertions, and a fake dials nothing — so the path an action is asked for at
 * and the argument it carries have nowhere to be checked there. They are
 * checked here, and they are worth checking: the path is what `N1-R4` and
 * `N2-R12` rest on, and the argument is the difference between stopping one
 * service and stopping the form it belongs to.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack these are asked of. */
function theSupervisedStack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** What a stack answers a verb with. */
function anAcknowledgement(): MockResponse
{
    return MockResponse::make(
        (string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'down', 'job' => 'a-job']]),
    );
}

/** The request the adapter actually sent, having said that. */
function whatWasSentSaying(AgreedTo $agreed): PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([anAcknowledgement()]);

    new Supervisors(new PinnedClients(), SequencedEntropy::counting())->told(
        theSupervisedStack(),
        Session::of('a-session-not-a-secret'),
        $agreed,
    );

    $sent = $mock->getLastPendingRequest();

    // Raised rather than expected, so the type is narrowed for whoever reads
    // it next: a case that asserted about a request nobody sent would pass on
    // an adapter that had stopped sending one.
    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

/**
 * A stop, which is the verb these cases say when the verb is not the subject.
 */
function aStopOfOneService(): AgreedTo
{
    return AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr'));
}

/**
 * The key one attempt travelled under.
 *
 * Raised rather than expected where there is none, for the reason
 * {@see whatWasSentSaying()} raises: a case comparing two absent keys would
 * find them equal and report that nothing was reused.
 */
function theKeyIn(?PendingRequest $sent): string
{
    $key = $sent?->headers()->get(Api::IDEMPOTENCY_HEADER);

    if (! is_string($key) || $key === '') {
        throw new RuntimeException('That attempt named no key, so this case read nothing.');
    }

    return $key;
}

/**
 * The key each of several attempts travelled under, through one adapter.
 *
 * One {@see Supervisors} for all of them deliberately. Building a fresh one per
 * attempt would make every key different by construction and prove nothing
 * about the object a screen actually holds, which is bound once and told many
 * times.
 *
 * @param list<MockResponse> $answers
 *
 * @return list<string>
 */
function theKeysOfAttemptsAnswered(array $answers): array
{
    MockClient::destroyGlobal();
    $mock = MockClient::global($answers);

    $supervisors = new Supervisors(new PinnedClients(), SequencedEntropy::counting());
    $keys = [];

    foreach ($answers as $ignored) {
        $supervisors->told(theSupervisedStack(), Session::of('a-session-not-a-secret'), aStopOfOneService());

        $keys[] = theKeyIn($mock->getLastPendingRequest());
    }

    return $keys;
}

it('N1-R42 — names the attempt in the header, and not in what the action takes', function (): void {
    $sent = whatWasSentSaying(aStopOfOneService());

    // Compared against what `Entropy` would hand out first, so this says where
    // the name came from as well as that there was one. A key built out of
    // anything this side already knows — the stack, the verb, the service — is
    // the same key on every attempt at the same thing, which is the shape that
    // reads as correct and replays.
    //
    // The body stays the action's arguments. That surface reads them against
    // the closed list the action offers and refuses a field it does not name,
    // so a key put there would turn every verb into a refusal.
    expect($sent->headers()->get(Api::IDEMPOTENCY_HEADER))
        ->toBe(SequencedEntropy::counting()->nonce()->shown())
        ->and($sent->body()?->all())->toBe(['services' => ['sonarr']]);
});

it('N1-R42 — a second attempt is a second name, through the same adapter', function (): void {
    // The requirement's load-bearing half. A key that is minted once and held
    // makes the second verb a re-send of the first, which is the one thing a
    // key exists to stop being possible.
    [$first, $second] = theKeysOfAttemptsAnswered([anAcknowledgement(), anAcknowledgement()]);

    expect($second)->not->toBe($first);
});

it('N1-R42 — an attempt that was refused is not tried again under its own name', function (): void {
    // The reconnection case, which is where holding a key looks kindest. The
    // first attempt does not reach the stack; the operator presses again, and
    // that is a new attempt rather than a replay of one nobody can see the
    // moment of any more (ADR-0020).
    [$refused, $again] = theKeysOfAttemptsAnswered([
        MockResponse::make('the stack could not be reached', 503),
        anAcknowledgement(),
    ]);

    expect($again)->not->toBe($refused);
});

it('N2-R7 — asks for a verb at the path the SDK composes for it', function (): void {
    $sent = whatWasSentSaying(AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr')));

    // `down`, not `stop`. The value is lemonfiber's word for the verb, and a
    // name that surface does not offer would be refused by name — which is the
    // failure this case exists to have happen here rather than on a phone.
    expect($sent->getUrl())->toEndWith('/api/actions/down');
});

it('N2-R7 — names one service under `services` and nothing else', function (): void {
    $sent = whatWasSentSaying(AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr')));

    expect($sent->body()?->all())->toBe(['services' => ['sonarr']]);
});

it('N2-R7 — names a whole form under `forms` and nothing else', function (): void {
    // The two are different requests on that surface rather than one with an
    // option, so an empty list beside the one that applies would be a second,
    // silent subject in every request.
    $sent = whatWasSentSaying(AgreedTo::theForm(WhatToDoWithIt::Restart, Form::called('downloads')));

    expect($sent->getUrl())->toEndWith('/api/actions/restart');
    expect($sent->body()?->all())->toBe(['forms' => ['downloads']]);
});

it('N2-R7 — asks for a start at the same door as a stop', function (): void {
    $sent = whatWasSentSaying(AgreedTo::theForm(WhatToDoWithIt::Start, Form::called('media')));

    expect($sent->getUrl())->toEndWith('/api/actions/up');
});

it('N1-R17 — reads what is running at the one endpoint for it', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'status',
            'data' => ['condition' => 'inactive', 'forms' => [], 'services' => []],
        ]),
    )]);

    new Supervisors(new PinnedClients(), SequencedEntropy::counting())->running(theSupervisedStack(), Session::of('a-session-not-a-secret'));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/status');
});
