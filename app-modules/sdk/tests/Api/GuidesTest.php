<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function afterEach;
use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Sdk\Api\Guides;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function str_repeat;

use Tests\Support\WalkthroughsToFollow;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine the adapter is pointed at. */
function theStackTheGuideWalks(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** One line carried out of an arm. */
final readonly class WhatTheGuideSaid
{
    public function __construct(public string $said) {}
}

/**
 * The adapter, answered with this body.
 *
 * @param array<array-key, mixed> $body
 */
function aGuideAnswering(array $body, int $status = 200): Guides
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($body), $status)]);

    return new Guides(new PinnedClients());
}

/** What starting a walk came to, as a line. */
function whatStartingCameTo(Guides $guide): string
{
    return $guide->walk(theStackTheGuideWalks(), Session::of('a-session-not-a-secret'), WhatToWalk::called('Sintel'))->either(
        started: static fn(Job $job): WhatTheGuideSaid => new WhatTheGuideSaid($job->shown()),
        met: static fn(Obstacle $why): WhatTheGuideSaid => new WhatTheGuideSaid($why->name),
    )->said;
}

/** What asking after a walk came to, as a line. */
function whatFollowingCameTo(Guides $guide): string
{
    return $guide->whatBecameOf(theStackTheGuideWalks(), Session::of('a-session-not-a-secret'), Job::named('a-walk'))->either(
        stillRunning: static fn(): WhatTheGuideSaid => new WhatTheGuideSaid('running'),
        done: static fn(AWalkthrough $walk): WhatTheGuideSaid => new WhatTheGuideSaid($walk->state()->value),
        ended: static fn(): WhatTheGuideSaid => new WhatTheGuideSaid('ended'),
        met: static fn(Obstacle $why): WhatTheGuideSaid => new WhatTheGuideSaid($why->name),
    )->said;
}

// The bodies below are each broken on purpose, so none is one a stack sends.
// `job` is stood in for and not judged: its version or its name is what each case takes away.
// `status` is stood in for and not judged: it is an answer about something else, and that is the case.
// `walkthrough` is stood in for and not judged: its version or what it proves is what each case takes away.

it('reads a start it cannot follow as a stack that did not answer, whatever made it unreadable', function (array $body): void {
    expect(whatStartingCameTo(aGuideAnswering($body)))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'a contract version it does not speak' => [['api_version' => 99, 'kind' => 'job', 'data' => ['job' => 'a-walk']]],
    'an answer about something else' => [['api_version' => 1, 'kind' => 'status', 'data' => []]],
    'a handle with no name' => [['api_version' => 1, 'kind' => 'job', 'data' => []]],
]);

it('reads a record it cannot read as a stack that did not answer, whatever made it unreadable', function (array $body): void {
    expect(whatFollowingCameTo(aGuideAnswering($body)))->toBe(Obstacle::StackDidNotAnswer->name);
})->with([
    'a contract version it does not speak' => [['api_version' => 99, 'kind' => 'walkthrough', 'data' => WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt()]],
    'an answer about something else' => [['api_version' => 1, 'kind' => 'status', 'data' => []]],
    'a record missing what it proves' => [['api_version' => 1, 'kind' => 'walkthrough', 'data' => ['state' => 'complete']]],
]);

it('reads a finished record through the handle', function (): void {
    expect(whatFollowingCameTo(aGuideAnswering(WalkthroughsToFollow::whatAStackSaysOfTheWalkThatWorked())))->toBe('complete');
});
