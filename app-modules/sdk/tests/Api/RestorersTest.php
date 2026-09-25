<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Restorers;
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

/** The stack a copy is put back on. */
function theStackTheRestorerAsks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A listing of one copy, its data going where a case says. */
function aListingWhoseDataGoes(WhereTheDataGoes $data): WhatPuttingItBackWouldDo
{
    return WhatPuttingItBackWouldDo::listed(
        ACopy::named('lemonfiber-20260924-0300-full'),
        'restore-the-whole-stack-0.9.0',
        ScopeOfACopy::theWholeStack(),
        '0.9.0',
        '2026-09-24T03:00:00Z',
        WhatACopyHolds::these(),
        older: false,
        data: $data,
    );
}

/** The last request a mock was sent, raised on where it was sent none. */
function theRequestTheRestorerSent(MockClient $mock): PendingRequest
{
    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks what putting a copy back would do by naming it, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Restorers(new PinnedClients(), SequencedEntropy::counting())->rehearse(theStackTheRestorerAsks(), Session::of('a-session-not-a-secret'), ACopy::named('lemonfiber-20260924-0300-full'));

    $sent = theRequestTheRestorerSent($mock);

    // No yes and no key: without the yes the stack reads the copy and changes
    // nothing, and a key would name an attempt at nothing.
    expect($sent->getUrl())->toEndWith('/api/actions/restore')
        ->and($sent->body()?->all())->toBe(['archive' => 'lemonfiber-20260924-0300-full'])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBeNull();
});

it('puts the copy back against the listing it was shown, re-pointing only where the listing said so', function (WhereTheDataGoes $data, bool $repoint): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('not an envelope at all')]);

    new Restorers(new PinnedClients(), SequencedEntropy::counting())->putBack(theStackTheRestorerAsks(), Session::of('a-session-not-a-secret'), aListingWhoseDataGoes($data));

    $sent = theRequestTheRestorerSent($mock);

    expect($sent->getUrl())->toEndWith('/api/actions/restore')
        ->and($sent->body()?->all())->toBe([
            'archive' => 'lemonfiber-20260924-0300-full',
            'confirm' => true,
            'offer' => 'restore-the-whole-stack-0.9.0',
            'repoint' => $repoint,
        ])
        ->and($sent->headers()->get(Api::IDEMPOTENCY_HEADER))->toBe(SequencedEntropy::counting()->nonce()->shown());
})->with([
    'back where it was' => [WhereTheDataGoes::whereItWas(), false],
    'somewhere else' => [WhereTheDataGoes::elsewhere(ARelocation::from('/srv/old', '/srv/new')), true],
]);

it('asks after a restore at the handle it was answered with', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make('{"error":"no such job"}', 404)]);

    new Restorers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(theStackTheRestorerAsks(), Session::of('a-session-not-a-secret'), Job::named('a-restore'));

    expect(theRequestTheRestorerSent($mock)->getUrl())->toEndWith('/api/jobs/a-restore');
});
