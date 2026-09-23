<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereItStopsShort;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Recorders;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsARecord;
use Tests\Support\WhatTheContractAccepts;

// The History contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `HostingContractTest`'s argument one endpoint along: every
// test of the record screen hands its subject an `AStackThatKeepsARecord` and
// never opens a socket, so a fake easier to satisfy than the adapter would
// enforce *an empty record is not an unreadable one* against a stack that
// always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose record is asked for. */
function aStackThatKeepsARecordOfItself(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionTheRecordIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What both implementations answer with. */
function theSameRecord(): TheRecord
{
    return TheRecord::reaching(
        'The last 90 days',
        Change::made('Pointed Sonarr at the new library', 'reconfigure', 'sonarr', WhenItWasMade::at(Instant::atEpochSeconds(1_790_142_840)), HowFarItGoesBack::Partial, 2)
            ->stoppingShort(WhereItStopsShort::suggesting('The old library was deleted', 'Restore it from the last backup first')),
        Change::made('Set up the download client', 'seed', 'qbittorrent', WhenItWasMade::at(Instant::atEpochSeconds(1_790_110_000)), HowFarItGoesBack::Whole, 1),
        Change::made('Wrote the first configuration', 'seed', 'lemonfiber', WhenItWasMade::unreadable(), HowFarItGoesBack::None, 1),
    );
}

/**
 * The payload a stack sends for that record.
 *
 * The first row's reversal is a parameter rather than something a case reaches
 * in and overwrites, for {@see whatAHostingMachineSends()}'s reason.
 *
 * @return array<string, mixed>
 */
function whatAStackKeepingARecordSends(string $reversalOfTheFirst = 'partial'): array
{
    return [
        'api_version' => 1,
        'kind' => 'history',
        'data' => [
            'horizon' => 'The last 90 days',
            'changes' => [
                [
                    'did' => 'Pointed Sonarr at the new library',
                    'operation' => 'reconfigure',
                    'target' => 'sonarr',
                    'at' => '1790142840',
                    'reversal' => $reversalOfTheFirst,
                    'alongside' => 2,
                    'because' => 'The old library was deleted',
                    'instead' => 'Restore it from the last backup first',
                ],
                [
                    'did' => 'Set up the download client',
                    'operation' => 'seed',
                    'target' => 'qbittorrent',
                    'at' => '1790110000',
                    'reversal' => 'whole',
                    'alongside' => 1,
                ],
                [
                    'did' => 'Wrote the first configuration',
                    'operation' => 'seed',
                    'target' => 'lemonfiber',
                    'at' => '0',
                    'reversal' => 'none',
                    'alongside' => 1,
                ],
            ],
        ],
    ];
}

/**
 * The payload a stack that has changed nothing sends.
 *
 * @return array<string, mixed>
 */
function whatAStackThatChangedNothingSends(): array
{
    return ['api_version' => 1, 'kind' => 'history', 'data' => ['horizon' => 'The last 90 days', 'changes' => []]];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): History>
 */
function everyWayOfAskingForTheRecord(MockResponse $answered, ?Obstacle $why = null, ?TheRecord $record = null): array
{
    return [
        'the fake' => static fn(): History => $why instanceof Obstacle
            ? AStackThatKeepsARecord::met($why)
            : AStackThatKeepsARecord::with($record ?? theSameRecord()),
        'the adapter' => static function () use ($answered): History {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Recorders(new PinnedClients());
        },
    ];
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheRecordTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the record says, folded to one line, so two answers can be compared. */
function everythingTheRecordSays(History $history): string
{
    return $history->recordedOn(aStackThatKeepsARecordOfItself(), theSessionTheRecordIsAskedWith())->either(
        record: static function (TheRecord $record): WhatTheRecordTurnedOutToSay {
            $rows = [$record->horizon()];

            foreach ($record as $change) {
                $rows[] = sprintf(
                    '%s/%s/%s/%s/%s/%d/%s',
                    $change->did(),
                    $change->operation(),
                    $change->target(),
                    $change->when()->either(
                        at: static fn(Instant $at): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay(sprintf('%d', $at->epochSeconds())),
                        unreadable: static fn(): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay('clock unreadable'),
                    )->said,
                    $change->reversal()->value,
                    $change->alongside(),
                    $change->stopsShort(
                        there: static fn(WhereItStopsShort $where): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay(
                            sprintf('%s/%s', $where->why(), $where->instead(
                                said: static fn(string $what): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay($what),
                                nothing: static fn(): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay('-'),
                            )->said),
                        ),
                        nowhere: static fn(): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay('-/-'),
                    )->said,
                );
            }

            return new WhatTheRecordTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatTheRecordTurnedOutToSay => new WhatTheRecordTurnedOutToSay($why->value),
    )->said;
}

it('N11-R1 — comes away with how far back it goes and every change, in the stack\'s order', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackKeepingARecordSends()));

    foreach (everyWayOfAskingForTheRecord($answered) as $which => $make) {
        expect(everythingTheRecordSays($make()))->toBe(
            'The last 90 days'
            . ' | Pointed Sonarr at the new library/reconfigure/sonarr/1790142840/partial/2/The old library was deleted/Restore it from the last backup first'
            . ' | Set up the download client/seed/qbittorrent/1790110000/whole/1/-/-'
            . ' | Wrote the first configuration/seed/lemonfiber/clock unreadable/none/1/-/-',
            $which,
        );
    }
});

it('N11-R9 — a stack that changed nothing answers with an empty record, not an obstacle', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackThatChangedNothingSends()));

    foreach (everyWayOfAskingForTheRecord($answered, record: TheRecord::reaching('The last 90 days')) as $which => $make) {
        expect(everythingTheRecordSays($make()))->toBe('The last 90 days', $which);
    }
});

it('N11-R9 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForTheRecord($answered, $why) as $which => $make) {
            expect(everythingTheRecordSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N11-R9 — a record this app cannot read is an obstacle, not a shorter record', function (): void {
    // The direction of error that matters. A row dropped for being unreadable
    // is a change the screen says did not happen.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackKeepingARecordSends('mostly')))]);

    expect(everythingTheRecordSays(new Recorders(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // Both bodies: the empty record is a shape of its own, and a suite judging
    // only the full one would be judging the arm a new stack takes least.
    expect(WhatTheContractAccepts::complaintsAbout('HistoryEnvelope', whatAStackKeepingARecordSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('HistoryEnvelope', whatAStackThatChangedNothingSends()))
        ->toBe([], "The empty payload this suite stands in for a stack with is not one a stack would send.\n");
});
