<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Shelves;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

/** The machine the adapter asks after a shelf on. */
function theStackWhoseShelfTheAdapterAsksAfter(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('h', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/**
 * The request the adapter actually sent, asking after a member's shelf.
 *
 * The mock is made here and held rather than through a helper that replaces
 * the global client, since a handle taken before that replacement is a handle
 * to a client nothing dialled.
 */
function whatWasSentAskingAfterAShelf(Whose $whose): ?PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Shelves(new PinnedClients())->theShelfOf(
        theStackWhoseShelfTheAdapterAsksAfter(),
        Session::of('a-session-not-a-secret'),
        $whose,
    );

    return $mock->getLastPendingRequest();
}

/**
 * The same request, insisted upon.
 *
 * Raised here rather than expected in a case, for the reason
 * {@see Shelves} is written the way it is: a case that
 * asserted about a request nobody sent would pass on an adapter that had
 * stopped sending one. In a function rather than a closure, since a checked
 * exception raised in one is refused.
 */
function theRequestAskingAfterAShelf(Whose $whose): PendingRequest
{
    $sent = whatWasSentAskingAfterAShelf($whose);

    return $sent instanceof PendingRequest
        ? $sent
        : throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
}

it('N3-R14 — names whose shelf it is asking for', function (): void {
    // There is no whole-household reading: the list is read from the media
    // server *as* an account, so a request that named nobody would be asking
    // a question the endpoint cannot answer — and one that named the wrong
    // member would hand somebody another member's library.
    $sent = theRequestAskingAfterAShelf(Whose::member('ada'));

    expect($sent->query()->all())->toHaveKey('member')
        ->and($sent->query()->all()['member'])->toBe('ada')
        ->and($sent->getUrl())->toContain(Api::HELD_ENDPOINT);
});

it('N3-R14 — asks nothing at all where the session is the operator\'s', function (): void {
    // Refused before the round trip rather than after it. The operator is not
    // an account the media server can read a shelf as, so a request naming
    // them is one the stack would turn down — and spending it would be asking
    // a question this side already knows the answer to.
    expect(whatWasSentAskingAfterAShelf(Whose::theOperator()))->toBeNull();
});
