<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\WhatTheGrantCameTo;
use Modules\Sdk\Api\Grantors;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatGrants;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The Granting contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** What a stack grants in this file: thirty-two hexadecimal digits, built rather than written out. */
function whatTheStackGrants(): string
{
    return str_repeat('0a', 16);
}

/** The stack a grant is asked of. */
function aStackThatIsAskedForAGrant(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What a stack answers a grant with, changed where a case says.
 *
 * @param  array<array-key, mixed> $changed
 * @return array<string, mixed>
 */
function whatAStackGrants(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'grant', 'data' => [
        'member' => 'Ada',
        'granted' => true,
        'token' => whatTheStackGrants(),
        'lasts_until' => '2026-11-08',
        'rehearsed' => false,
        ...$changed,
    ]];
}

/** The grant that payload stands for: lapsing as 8 November 2026 ends, in UTC. */
function theSameGrant(): AGrant
{
    return AGrant::of(whatTheStackGrants(), Instant::atEpochSeconds(1_794_182_400));
}

/**
 * Both ways of granting, each set up to say the same.
 *
 * @return array<string, Closure(): Granting>
 */
function everyWayOfGranting(MockResponse $answered, WhatTheGrantCameTo $same): array
{
    return [
        'the fake' => static fn(): Granting => $same->either(
            granted: static fn(AGrant $grant): AStackThatGrants => AStackThatGrants::granting($grant),
            refused: static fn(Obstacle $why): AStackThatGrants => AStackThatGrants::refusing($why),
        ),
        'the adapter' => static function () use ($answered): Granting {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Grantors(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** What asking for a grant came to, as one line. */
function whatTheGrantCameTo(Granting $granting): string
{
    return $granting->aGrantFor(aStackThatIsAskedForAGrant(), Session::of('a-session-not-a-secret'), ThisDevice::named('this-device'))->either(
        granted: static fn(AGrant $grant): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s until %d', $grant->forTheDoor(), $grant->lapsesAt()->epochSeconds())),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('reads the grant the core answered, lapsing as its last day ends', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackGrants()));

    foreach (everyWayOfGranting($answered, WhatTheGrantCameTo::granted(theSameGrant())) as $which => $build) {
        expect(whatTheGrantCameTo($build()))->toBe(sprintf('%s until 1794182400', whatTheStackGrants()), $which);
    }
});

it('says the stack did not answer where the grant cannot be played with', function (array $changed): void {
    $answered = MockResponse::make((string) json_encode(whatAStackGrants($changed)));

    foreach (everyWayOfGranting($answered, WhatTheGrantCameTo::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))) as $which => $build) {
        expect(whatTheGrantCameTo($build()))->toBe(KindOfObstacle::StackDidNotAnswer->name, $which);
    }
})->with([
    'no token' => [['token' => null, 'granted' => false]],
    'a token the door would not take' => [['token' => 'NOT-A-TOKEN']],
    'no last day' => [['lasts_until' => 'next month']],
    'a day that is not one' => [['lasts_until' => '2026-02-30']],
]);

it('says the session was refused where the core would not take it', function (): void {
    $answered = MockResponse::make('{"error":"no"}', 401);

    foreach (everyWayOfGranting($answered, WhatTheGrantCameTo::refused(Obstacle::of(KindOfObstacle::CredentialWasRefused))) as $which => $build) {
        expect(whatTheGrantCameTo($build()))->toBe(KindOfObstacle::CredentialWasRefused->name, $which);
    }
});

it('asks for the grant under this device\'s id and names no member', function (): void {
    $sent = null;
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent = [$asked->getRequest()->resolveEndpoint(), $asked->body()?->all()];

            return MockResponse::make((string) json_encode(whatAStackGrants()));
        },
    ]);

    new Grantors(new PinnedClients(), SequencedEntropy::counting())->aGrantFor(aStackThatIsAskedForAGrant(), Session::of('a-session-not-a-secret'), ThisDevice::named('this-device'));

    expect($sent)->toBe(['/api/actions/grant', ['name' => null, 'device' => 'this-device']]);
});

it('stands in for a stack with a grant the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('GrantEnvelope', whatAStackGrants()))->toBe([]);
});
