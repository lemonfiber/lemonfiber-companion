<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Sdk\Api\Guides;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\WalkthroughsToFollow;
use Tests\Support\WhatTheContractAccepts;

// The WalkingThrough contract, run against the adapter and against the fake.
//
// What both must agree on: that starting one answers the handle the stack
// named or what stood in the way, that the finished record carries every line
// as said and in the order said, that already here is carried as its own fact,
// and that a job the stack no longer knows is ended rather than failed.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a walkthrough is started on. */
function aStackThatCanWalk(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionAWalkIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheWalkCameTo
{
    public function __construct(public string $said) {}
}

/** A finished walkthrough, every field of it folded into one line. */
function everythingTheWalkSaid(AWalkthrough $walk): string
{
    $lines = [];

    foreach ($walk->lines() as $line) {
        $lines[] = sprintf('%s:%s:%s', $line->step()->value, $line->said(), $line->detail(
            said: static fn(string $detail): WhatTheWalkCameTo => new WhatTheWalkCameTo($detail),
            nothing: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('(none)'),
        )->said);
    }

    $suggestions = [];

    foreach ($walk->suggestions() as $suggestion) {
        $suggestions[] = $suggestion;
    }

    $next = [];

    foreach ($walk->handover() as $step) {
        $next[] = $step->value;
    }

    return implode('|', [
        $walk->shape()->value,
        $walk->state()->value,
        $walk->proves(),
        $walk->item()->either(
            named: static fn(string $item): WhatTheWalkCameTo => new WhatTheWalkCameTo($item),
            nothingChosen: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('(no item)'),
        )->said,
        implode(';', $lines),
        implode(';', $suggestions),
        $walk->wentOnInTheBackground() ? 'in the background' : 'waited out',
        $walk->wasAlreadyHere() ? 'already here' : 'fetched',
        $walk->link(
            linked: static fn(HowTheImportLinked $link): WhatTheWalkCameTo => new WhatTheWalkCameTo($link->value),
            notImported: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('(no link)'),
        )->said,
        implode(',', $next),
        $walk->stopped(
            at: static function (WhereItStopped $stopped): WhatTheWalkCameTo {
                $logs = [];

                foreach ($stopped->logs() as $log) {
                    $logs[] = sprintf('[%s]', $log);
                }

                return new WhatTheWalkCameTo(sprintf('%s:%s:%s:%s', $stopped->step()->value, $stopped->why()->value, $stopped->remedy(), implode('', $logs)));
            },
            didNotStop: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('(did not stop)'),
        )->said,
    ]);
}

/** What asking after the walkthrough produced, as one line. */
function whatTheWalkBecame(WalkingThrough $walking): string
{
    return $walking->whatBecameOf(aStackThatCanWalk(), theSessionAWalkIsAskedWith(), Job::named(AStackThatWalksThrough::THE_JOB))->either(
        stillRunning: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('still running'),
        done: static fn(AWalkthrough $walk): WhatTheWalkCameTo => new WhatTheWalkCameTo(everythingTheWalkSaid($walk)),
        ended: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('ended'),
        met: static fn(Obstacle $why): WhatTheWalkCameTo => new WhatTheWalkCameTo($why->name),
    )->said;
}

/** What starting a walkthrough produced, as one line. */
function howTheWalkStarted(WalkingThrough $walking, WhatToWalk $asked): string
{
    return $walking->walk(aStackThatCanWalk(), theSessionAWalkIsAskedWith(), $asked)->either(
        started: static fn(Job $job): WhatTheWalkCameTo => new WhatTheWalkCameTo(sprintf('following %s', $job->shown())),
        met: static fn(Obstacle $why): WhatTheWalkCameTo => new WhatTheWalkCameTo($why->name),
    )->said;
}

/**
 * Both implementations, each set up to say the same.
 *
 * @return array<string, Closure(): WalkingThrough>
 */
function everyWayOfWalkingThrough(MockResponse $answered, HowTheWalkthroughIsGoing $became): array
{
    return [
        'the fake' => static fn(): WalkingThrough => AStackThatWalksThrough::whichWalked($became),
        'the adapter' => static function () use ($answered): WalkingThrough {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Guides(new PinnedClients());
        },
    ];
}

/**
 * Both implementations, each unable to get through for the same reason.
 *
 * @return array<string, Closure(): WalkingThrough>
 */
function everyWayOfNotWalkingThrough(MockResponse $answered, Obstacle $why): array
{
    return [
        'the fake' => static fn(): WalkingThrough => AStackThatWalksThrough::met($why),
        'the adapter' => static function () use ($answered): WalkingThrough {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Guides(new PinnedClients());
        },
    ];
}

/**
 * A finished job answering with this payload.
 *
 * @param array<string, mixed> $data
 */
function aFinishedWalk(array $data): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'walkthrough', 'data' => $data]));
}

/** The handle a stack answers a start with. */
function theHandleAWalkAnswers(): MockResponse
{
    return MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'walkthrough', 'job' => AStackThatWalksThrough::THE_JOB],
    ]), 202);
}

/**
 * The ways of not getting through, each with the obstacle it must come to.
 *
 * @return list<array{MockResponse, Obstacle}>
 */
function theWaysAWalkIsNotReached(): array
{
    return [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];
}

it('carries a finished walk whole, each line as said and in the order said', function (): void {
    $answered = aFinishedWalk(WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt());

    foreach (everyWayOfWalkingThrough($answered, HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatWorked())) as $which => $build) {
        expect(whatTheWalkBecame($build()))->toBe(implode('|', [
            'pipeline',
            'complete',
            'That every link from the indexers to the library works.',
            'Big Buck Bunny',
            'searching:Searching indexers…:3 indexers, 47 results;choosing:Selecting best match…:1080p, matches your Balanced preset;'
            . 'grabbing:Sending to download client…:SABnzbd, via usenet;downloading:Downloading…:2.1 GB · 14 MB/s · ~2m;'
            . 'importing:Importing…:copied to /data/media/movies;available:Available in Jellyfin:(none)',
            '',
            'waited out',
            'fetched',
            'copied',
            'more-content,household,client-apps',
            '(did not stop)',
        ]), $which);
    }
});

it('carries a walk that stopped with where, why, the remedy and every log line', function (): void {
    $answered = aFinishedWalk(WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt());

    foreach (everyWayOfWalkingThrough($answered, HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkThatMatchedNothing())) as $which => $build) {
        expect(whatTheWalkBecame($build()))->toBe(implode('|', [
            'pipeline',
            'failed',
            'That every link from the indexers to the library works.',
            'A Film Nobody Seeded',
            'searching:Searching indexers…:3 indexers, 0 results',
            'Big Buck Bunny;Sintel',
            'waited out',
            'fetched',
            '(no link)',
            '',
            'searching:nothing-matched:Try one of the suggestions, which are well seeded.:[prowlarr: query returned 0 results][]',
        ]), $which);
    }
});

it('carries already here as its own fact, beside a handover with nothing in it', function (): void {
    $answered = aFinishedWalk(WalkthroughsToFollow::theWalkOfSomethingAlreadyHereAsAStackSendsIt());

    foreach (everyWayOfWalkingThrough($answered, HowTheWalkthroughIsGoing::done(WalkthroughsToFollow::aWalkOfSomethingAlreadyHere())) as $which => $build) {
        expect(whatTheWalkBecame($build()))->toBe(implode('|', [
            'library-only',
            'complete',
            'That the media server can see what is on disk.',
            'Sintel',
            'choosing:Checking it is not already here…:It is already in the library',
            '',
            'in the background',
            'already here',
            'hardlinked',
            '',
            '(did not stop)',
        ]), $which);
    }
});

it('says a walk still running is running', function (): void {
    foreach (everyWayOfWalkingThrough(theHandleAWalkAnswers(), HowTheWalkthroughIsGoing::stillRunning()) as $which => $build) {
        expect(whatTheWalkBecame($build()))->toBe('still running', $which);
    }
});

it('says a walk the stack no longer knows is ended, not unreachable and not running', function (): void {
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);
    $letGo = MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'walkthrough', 'job' => AStackThatWalksThrough::THE_JOB],
    ]), 200);

    foreach ([$forgotten, $letGo] as $answered) {
        foreach (everyWayOfWalkingThrough($answered, HowTheWalkthroughIsGoing::ended()) as $which => $build) {
            expect(whatTheWalkBecame($build()))->toBe('ended', $which);
        }
    }
});

it('tells a refused session from a stack that is not answering, asking after one', function (): void {
    foreach (theWaysAWalkIsNotReached() as [$answered, $why]) {
        foreach (everyWayOfNotWalkingThrough($answered, $why) as $which => $build) {
            expect(whatTheWalkBecame($build()))->toBe($why->name, $which);
        }
    }
});

it('comes away from a start with the handle the stack named', function (): void {
    foreach (everyWayOfWalkingThrough(theHandleAWalkAnswers(), HowTheWalkthroughIsGoing::stillRunning()) as $which => $build) {
        expect(howTheWalkStarted($build(), WhatToWalk::called('Big Buck Bunny')))
            ->toBe(sprintf('following %s', AStackThatWalksThrough::THE_JOB), $which);
    }
});

it('comes away from a start that did not happen with the obstacle rather than a handle', function (): void {
    foreach (theWaysAWalkIsNotReached() as [$answered, $why]) {
        foreach (everyWayOfNotWalkingThrough($answered, $why) as $which => $build) {
            expect(howTheWalkStarted($build(), WhatToWalk::called('Big Buck Bunny')))->toBe($why->name, $which);
        }
    }
});

it('the fake remembers what it was asked to walk and the handle it was asked after by', function (): void {
    $walking = AStackThatWalksThrough::whichWalked(HowTheWalkthroughIsGoing::stillRunning());

    expect($walking->walked())->toBe([])
        ->and($walking->followed())->toBe([]);

    howTheWalkStarted($walking, WhatToWalk::called('Sintel'));
    whatTheWalkBecame($walking);

    expect($walking->walked())->toHaveCount(1)
        ->and($walking->walked()[0]->either(
            named: static fn(string $item): WhatTheWalkCameTo => new WhatTheWalkCameTo($item),
            likeliest: static fn(): WhatTheWalkCameTo => new WhatTheWalkCameTo('(likeliest)'),
        )->said)->toBe('Sintel')
        ->and($walking->followed())->toHaveCount(1)
        ->and($walking->followed()[0]->shown())->toBe(AStackThatWalksThrough::THE_JOB);
});

/** The request a start put on the wire. */
function whatAStartSent(WhatToWalk $asked): PendingRequest
{
    MockClient::destroyGlobal();
    $mock = MockClient::global([theHandleAWalkAnswers()]);

    howTheWalkStarted(new Guides(new PinnedClients()), $asked);

    $sent = $mock->getLastPendingRequest();

    if (! $sent instanceof PendingRequest) {
        throw new RuntimeException('The adapter sent nothing, so this case read nothing.');
    }

    return $sent;
}

it('asks for the walkthrough action, naming the item', function (): void {
    $sent = whatAStartSent(WhatToWalk::called('  Big Buck Bunny  '));

    expect($sent->getUrl())->toEndWith('/api/actions/walkthrough')
        ->and($sent->body()?->all())->toBe(['item' => 'Big Buck Bunny']);
});

it('names no item where none was typed, so the stack picks something likely to work', function (string $typed): void {
    expect(whatAStartSent(WhatToWalk::called($typed))->body()?->all())->toBe([]);
})->with(['nothing' => [''], 'only spaces' => ['   ']]);

it('asks after the handle the start answered', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([theHandleAWalkAnswers()]);

    whatTheWalkBecame(new Guides(new PinnedClients()));

    expect($mock->getLastPendingRequest()?->getUrl())->toEndWith(sprintf('/api/jobs/%s', AStackThatWalksThrough::THE_JOB));
});

it('refuses a finished walk it cannot read as a stack that did not answer', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([aFinishedWalk([...WalkthroughsToFollow::theWalkThatWorkedAsAStackSendsIt(), 'state' => 'wandering'])]);

    expect(whatTheWalkBecame(new Guides(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    $bodies = [
        WalkthroughsToFollow::whatAStackSaysOfTheWalkThatWorked(),
        ['api_version' => 1, 'kind' => 'walkthrough', 'data' => WalkthroughsToFollow::theWalkThatMatchedNothingAsAStackSendsIt()],
        ['api_version' => 1, 'kind' => 'walkthrough', 'data' => WalkthroughsToFollow::theWalkOfSomethingAlreadyHereAsAStackSendsIt()],
    ];

    foreach ($bodies as $body) {
        expect(WhatTheContractAccepts::complaintsAbout('WalkthroughEnvelope', $body))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});
