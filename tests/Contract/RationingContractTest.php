<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\Remark;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Quartermasters;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatRationsItsLine;
use Tests\Support\WhatTheContractAccepts;

// The Rationing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `TellingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose line is asked about. */
function aStackThatSharesItsLine(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameLine(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::CapWarning, 'Close to the month\'s cap', 'Half of 100 Mbit/s', 'No limit', Remarks::of('Measured at night'), Remarks::of('Plex streams'))
        ->measuredAt(WhatTheLineCarries::measured(12_500_000, 2_500_000, HowTheLineWasMeasured::Declared, Instant::atEpochSeconds(1_790_100_000), WhetherItGoesThroughTheTunnel::Beside))
        ->cappedAt(AMonthlyCap::of(1_000_000_000_000, WhatACapDoes::Pause)->standing(WhereTheMonthStands::Warning))
        ->withUploadCosting(Remark::said('Seeding back at a quarter slows the ratio', 'ratio'));
}

/**
 * The payload a stack sends for that line.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsLine(string $restraint = 'cap-warning'): array
{
    return [
        'api_version' => 1,
        'kind' => 'bandwidth',
        'data' => [
            'restraint' => $restraint,
            'means' => 'Close to the month\'s cap',
            'cautions' => ['Measured at night'],
            'untouched' => ['Plex streams'],
            'down' => ['limit' => ['as' => 'share', 'at' => 50], 'resolved' => ['is' => 'at', 'bytes_per_second' => 6_250_000], 'says' => 'Half of 100 Mbit/s'],
            'up' => ['limit' => ['as' => 'unlimited'], 'resolved' => ['is' => 'unlimited'], 'says' => 'No limit'],
            'capacity' => ['down' => 12_500_000, 'up' => 2_500_000, 'source' => 'declared', 'taken' => 1_790_100_000, 'through_tunnel' => false],
            'cap' => ['monthly' => 1_000_000_000_000, 'exceeded' => 'pause'],
            'reached' => 'warning',
            'ratio' => 'Seeding back at a quarter slows the ratio',
            'respite' => ['standing' => 'none'],
            'clients' => [],
            'applied' => false,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Rationing>
 */
function everyWayOfAskingHowTheLineIs(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Rationing => $why instanceof Obstacle
            ? AStackThatRationsItsLine::met($why)
            : AStackThatRationsItsLine::with(theSameLine()),
        'the adapter' => static function () use ($answered): Rationing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Quartermasters(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheLineTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheReadingSays(Rationing $rationing): string
{
    return $rationing->rationedOn(aStackThatSharesItsLine(), Session::of('a-session-not-a-secret'))->either(
        shared: static fn(HowTheLineIsShared $line): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay(sprintf(
            '%s|%s|%s|%s|%s|%s|%s',
            $line->stands()->value,
            $line->means(),
            $line->downSays(),
            $line->upSays(),
            $line->capacity(
                static fn(WhatTheLineCarries $c): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay(sprintf('%d/%d %s %s', $c->down(), $c->up(), $c->measuredAs()->value, $c->tunnel()->value)),
                static fn(): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay('unmeasured'),
            )->said,
            $line->cap(
                static fn(AMonthlyCap $c): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay(sprintf('%d %s %s', $c->monthly(), $c->does()->value, $c->stands(
                    static fn(WhereTheMonthStands $m): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay($m->value),
                    static fn(): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay('uncounted'),
                )->said)),
                static fn(): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay('uncapped'),
            )->said,
            $line->uploadCost(static fn(string $costs): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay($costs), static fn(): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay('-'))->said,
        )),
        met: static fn(Obstacle $why): WhatTheLineTurnedOutToSay => new WhatTheLineTurnedOutToSay($why->value),
    )->said;
}

it('N10-R4, N10-R5, N10-R6 — comes away with the line\'s standing, capacity and cap', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsLine()));

    foreach (everyWayOfAskingHowTheLineIs($answered) as $which => $make) {
        expect(everythingTheReadingSays($make()))->toBe(
            'cap-warning|Close to the month\'s cap|Half of 100 Mbit/s|No limit|12500000/2500000 declared beside|1000000000000 pause warning|Seeding back at a quarter slows the ratio',
            $which,
        );
    }
});

it('N10-R12 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingHowTheLineIs($answered, $why) as $which => $make) {
            expect(everythingTheReadingSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N10-R12 — a line this app cannot read is an obstacle, never an unlimited line', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsLine('throttled')))]);

    expect(everythingTheReadingSays(new Quartermasters(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BandwidthEnvelope', whatAStackSaysOfItsLine()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
