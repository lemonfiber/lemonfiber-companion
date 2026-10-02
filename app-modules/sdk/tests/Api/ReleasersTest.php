<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Releasers;
use RuntimeException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;

/**
 * What the adapter puts on the wire, which the contract cannot ask about: the
 * path, the arguments, and which of the two requests carries a key.
 */
afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a download is let go on. */
function theStackTheReleaserAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The last request a mock was sent, raised on where it was sent none. */
function theRequestTheReleaserSent(MockClient $mock): PendingRequest
{
    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks what stopping seeding would cost by naming the download, under a key of its own', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Releasers(new PinnedClients(), SequencedEntropy::counting())->whatItWouldCost(theStackTheReleaserAsks(), Session::of('a-session-not-a-secret'), ADownloadHeld::named('Show.Season1'));

    $sent = theRequestTheReleaserSent($mock);

    // No offer: without the offer's name the stack states the cost and lets
    // nothing go. The key is there as on every action.
    expect($sent->getUrl())->toEndWith('/api/actions/stop-seeding')
        ->and($sent->body()?->all())->toBe(['download' => 'Show.Season1'])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->not->toBeNull();
});

it('stops seeding against the offer it was shown, naming the offer as the yes and never a blanket confirm', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);
    $offer = WhatLettingItGoCosts::offered(
        ADownloadOnDisk::seeding('Show.Season1', 4_000, ARatio::inHundredths(80)),
        'The copy in the downloads tree goes with it',
        'stop-seeding-show-season1-4000',
    );

    new Releasers(new PinnedClients(), SequencedEntropy::counting())->stop(theStackTheReleaserAsks(), Session::of('a-session-not-a-secret'), $offer);

    $sent = theRequestTheReleaserSent($mock);

    expect($sent->getUrl())->toEndWith('/api/actions/stop-seeding')
        ->and($sent->body()?->all())->toBe(['download' => 'Show.Season1', 'offer' => 'stop-seeding-show-season1-4000'])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBe(SequencedEntropy::counting()->nonce()->shown());
});

it('asks after the offer and the yes at the handles they were answered with', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404), MockResponse::make('{"error":"no such job"}', 404)]);
    $releasers = new Releasers(new PinnedClients(), SequencedEntropy::counting());

    $releasers->whatTheOfferCameTo(theStackTheReleaserAsks(), Session::of('a-session-not-a-secret'), Job::named('an-offer'));

    expect(theRequestTheReleaserSent($mock)->getUrl())->toEndWith('/api/jobs/an-offer');

    $releasers->whatBecameOf(theStackTheReleaserAsks(), Session::of('a-session-not-a-secret'), Job::named('a-release'));

    expect(theRequestTheReleaserSent($mock)->getUrl())->toEndWith('/api/jobs/a-release');
});
