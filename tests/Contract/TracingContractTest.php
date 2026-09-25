<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchOfASeasonIsHere;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Sdk\Api\Followers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatTraces;
use Tests\Support\TracesToFollow;
use Tests\Support\WhatTheContractAccepts;

// The Tracing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `ExplainingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked where an item got to. */
function aStackThatFollowsItems(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Tracing>
 */
function everyWayOfFollowingAnItem(MockResponse $answered, WhereItGotTo $same, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Tracing => $why instanceof Obstacle
            ? AStackThatTraces::met($why)
            : AStackThatTraces::with($same),
        'the adapter' => static function () use ($answered): Tracing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Followers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheTraceTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything a trace says, folded to one line, so two answers can be compared. */
function everythingTheTraceSays(Tracing $tracing): string
{
    return $tracing->tracedOn(aStackThatFollowsItems(), Session::of('a-session-not-a-secret'), WhatToFollow::called('Severance'))->either(
        found: static fn(WhereItGotTo $trace): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay(sprintf('%s %s', $trace->item(), $trace->either(
            nothingAskedFor: static fn(): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay('nothing asked for'),
            followed: static fn(WhatTheTraceFound $found): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay(whatWasFound($found)),
        )->said)),
        met: static fn(Obstacle $why): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay($why->value),
    )->said;
}

/** What a followed trace found, as one line. */
function whatWasFound(WhatTheTraceFound $found): string
{
    $said = [$found->sure()->value, $found->got()->furthest()->value, $found->got()->stall()];

    foreach ($found->got()->stages() as $stage) {
        $said[] = sprintf('%s@%s:%s', $stage->stage()->value, $stage->service()->named(), $stage->at());
    }

    foreach ($found->history() as $moment) {
        $said[] = sprintf('%s:%s', $moment->happened()->value, $moment->at());
    }

    foreach ($found->disagreements() as $disagreement) {
        $said[] = $disagreement;
    }

    $said[] = $found->here()->either(
        whole: static fn(): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay('whole'),
        inParts: static fn(ASeriesCounted $series): WhatTheTraceTurnedOutToSay => new WhatTheTraceTurnedOutToSay(whatIsHere($series)),
    )->said;

    return implode('|', $said);
}

/** A counted series, as one line. */
function whatIsHere(ASeriesCounted $series): string
{
    $said = [sprintf('%d/%d+%d', $series->have(), $series->wanted(), $series->unmonitored())];

    foreach ($series->seasons() as $season) {
        $said[] = whatIsHereOfASeason($season);
    }

    return implode(';', $said);
}

/** One season, as one line. */
function whatIsHereOfASeason(HowMuchOfASeasonIsHere $season): string
{
    $episodes = [];

    foreach ($season->outstanding() as $episode) {
        $episodes[] = sprintf('S%dE%d %s %s', $episode->season(), $episode->number(), $episode->title(), $episode->stage()->value);
    }

    return sprintf('s%d %d/%d+%d [%s]', $season->season(), $season->have(), $season->wanted(), $season->unmonitored(), implode(',', $episodes));
}

it('comes away with how sure, how far, what was tried, where services disagree and what of it is here', function (): void {
    $answered = MockResponse::make((string) json_encode(TracesToFollow::whatAStackSaysOfTheSeries()));
    $said = null;

    foreach (everyWayOfFollowingAnItem($answered, TracesToFollow::aSeriesStuckDownloading()) as $which => $make) {
        $now = everythingTheTraceSays($make());
        $said ??= $now;

        expect($now)->toBe($said, $which)
            ->and($now)->toContain('uncertain|downloading|No peers have been seen for two days')
            ->and($now)->toContain('monitored@sonarr:|grabbed@sonarr:2026-09-20T10:00:00Z')
            ->and($now)->toContain('download-failed:2026-09-19T21:00:00Z')
            ->and($now)->toContain('8/10+1;s1 8/9+1 [S1E9 The We We Are downloading];s2 0/1+0 [S2E1 Hello, Ms. Cobel searching]');
    }
});

it('reads nothing asked for as an answer of its own, on both', function (): void {
    $answered = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'trace', 'data' => [...TracesToFollow::theSeriesAsAStackSendsIt(), 'matched' => false]]));

    foreach (everyWayOfFollowingAnItem($answered, WhereItGotTo::nothingAskedFor('Severance')) as $which => $make) {
        expect(everythingTheTraceSays($make()))->toBe('Severance nothing asked for', $which);
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfFollowingAnItem($answered, TracesToFollow::aSeriesStuckDownloading(), $why) as $which => $make) {
            expect(everythingTheTraceSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a trace this app cannot read is an obstacle, never a trace half-drawn', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'trace', 'data' => [
        ...TracesToFollow::theSeriesAsAStackSendsIt(),
        'coverage' => ['have' => 12, 'wanted' => 10, 'unmonitored' => 0, 'seasons' => []],
    ]]))]);

    expect(everythingTheTraceSays(new Followers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the trace endpoint, naming what to follow', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(TracesToFollow::whatAStackSaysOfTheSeries()))]);

    everythingTheTraceSays(new Followers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/trace')
        ->and($sent?->query()->all())->toBe(['term' => 'Severance']);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('TraceEnvelope', TracesToFollow::whatAStackSaysOfTheSeries()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
