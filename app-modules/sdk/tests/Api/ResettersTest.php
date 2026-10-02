<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Resetters;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;

/**
 * What the adapter puts on the wire, which the contract cannot ask about: the
 * path, the argument, and which of the two requests carries a key.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose configuration the resetter asks about. */
function theStackTheResetterAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The last request a mock was sent, raised on where it was sent none. */
function theRequestTheResetterSent(MockClient $mock): PendingRequest
{
    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks what putting the configuration back would revert without a yes, under a key of its own', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Resetters(new PinnedClients(), SequencedEntropy::counting())->wouldRevert(theStackTheResetterAsks(), Session::of('a-session-not-a-secret'));

    $sent = theRequestTheResetterSent($mock);

    // Without the yes the stack compares and writes nothing. The key is
    // there as on every action.
    expect($sent->getUrl())->toEndWith('/api/actions/reset')
        ->and($sent->body()?->all())->toBe(['confirm' => false])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->not->toBeNull();
});

it('puts the configuration back with the yes, under a key', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);
    $previewed = TheReset::previewed(TheStackEdits::these(AStackEdit::at('compose.yaml', '')), ConnectionsReverted::these());

    new Resetters(new PinnedClients(), SequencedEntropy::counting())->revert(theStackTheResetterAsks(), Session::of('a-session-not-a-secret'), AResetAgreed::to($previewed));

    $sent = theRequestTheResetterSent($mock);

    expect($sent->getUrl())->toEndWith('/api/actions/reset')
        ->and($sent->body()?->all())->toBe(['confirm' => true])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBe(SequencedEntropy::counting()->nonce()->shown());
});

it('asks after a reset at the handle it was answered with', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404)]);

    new Resetters(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(theStackTheResetterAsks(), Session::of('a-session-not-a-secret'), Job::named('a-reset'));

    expect(theRequestTheResetterSent($mock)->getUrl())->toEndWith('/api/jobs/a-reset');
});
