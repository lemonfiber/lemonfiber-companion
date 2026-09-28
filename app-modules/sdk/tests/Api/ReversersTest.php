<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Reversers;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;

/**
 * What the adapter puts on the wire, which the contract cannot ask about: the
 * path, the one argument, and which of the two requests carries a key.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a run is put back on. */
function theStackTheReverserAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The last request a mock was sent, raised on where it was sent none. */
function theRequestTheReverserSent(MockClient $mock): PendingRequest
{
    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks for the run by the stamp the record keeps it under, and carries a key', function (WhenItWasMade $when, string $stamp): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);
    $change = Change::made('Set LIBRARY_PATH', 'reconfigure', 'lemonfiber', $when, HowFarItGoesBack::Whole, 1);
    $agreed = ARunAgreedTo::by(TheRecord::reaching('the last ninety days', $change)->theRun(ARun::madeAt($when)));

    new Reversers(new PinnedClients(), SequencedEntropy::counting())->putBack(theStackTheReverserAsks(), Session::of('a-session-not-a-secret'), $agreed);

    $sent = theRequestTheReverserSent($mock);

    // No yes: the stack takes none for this, and the agreement was made on
    // this side against the record's rows.
    expect($sent->getUrl())->toEndWith('/api/actions/undo')
        ->and($sent->body()?->all())->toBe(['at' => $stamp])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBe(SequencedEntropy::counting()->nonce()->shown());
})->with([
    'a dated run' => [WhenItWasMade::at(Instant::atEpochSeconds(1_790_150_000)), '1790150000'],
    'a run the clock could not date' => [WhenItWasMade::unreadable(), '0'],
]);

it('asks after a run at the handle it was answered with, carrying no key', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404)]);

    new Reversers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(theStackTheReverserAsks(), Session::of('a-session-not-a-secret'), Job::named('an-undo'));

    $sent = theRequestTheReverserSent($mock);

    expect($sent->getUrl())->toEndWith('/api/jobs/an-undo')
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBeNull();
});
