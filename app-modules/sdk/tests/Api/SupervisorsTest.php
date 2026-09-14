<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;
use function json_encode;

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

    new Supervisors(new PinnedClients())->told(
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

    new Supervisors(new PinnedClients())->running(theSupervisedStack(), Session::of('a-session-not-a-secret'));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith('/api/status');
});
