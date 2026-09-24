<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\Outgoing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;
use Modules\Sdk\Api\Lookouts;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatLeavesIt;
use Tests\Support\WhatTheContractAccepts;

// The Outgoing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `ProvenanceContractTest`'s argument one endpoint along:
// every test of the screen hands its subject an `AStackThatSaysWhatLeavesIt`,
// so a fake easier to satisfy than the adapter would enforce *nothing leaves
// is not a list that could not be read* against a stack that always answers.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose connections are asked for. */
function aStackThatSaysWhatItSends(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameConnections(): WhatLeavesThisMachine
{
    return WhatLeavesThisMachine::of(
        OurRequests::of(
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Registry, WhereItGoes::to('ghcr.io', 'lscr.io'), 'To fetch the images the stack runs', 'The name and version of each image', WhetherItIsAllowed::Allowed, 'registry.pull', 'No service can be installed or updated'),
            ARequestOfOurs::described(WhatLemonfiberAsksFor::Updates, WhereItGoes::to(), 'To say when a newer lemonfiber is out', 'Nothing but the request itself', WhetherItIsAllowed::SwitchedOff, 'updates.check', 'Nobody hears that a version came out'),
        ),
        TheirRequests::of(
            ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata'),
            ARequestOfTheirs::recorded(ServiceId::called('gluetun'), '', 'Nothing of its own'),
            ARequestOfTheirs::unrecorded(ServiceId::called('my-fork')),
        ),
    );
}

/**
 * The payload a stack sends for those connections.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysLeavesIt(string $reachOfTheFirst = 'registry'): array
{
    return [
        'api_version' => 1,
        'kind' => 'outbound',
        'data' => [
            'ours' => [
                ['reach' => $reachOfTheFirst, 'destination' => ['ghcr.io', 'lscr.io'], 'purpose' => 'To fetch the images the stack runs', 'sends' => 'The name and version of each image', 'allowed' => true, 'switch' => 'registry.pull', 'cost' => 'No service can be installed or updated'],
                ['reach' => 'updates', 'destination' => [], 'purpose' => 'To say when a newer lemonfiber is out', 'sends' => 'Nothing but the request itself', 'allowed' => false, 'switch' => 'updates.check', 'cost' => 'Nobody hears that a version came out'],
            ],
            'theirs' => [
                ['service' => 'sonarr', 'destination' => 'thetvdb.com', 'purpose' => 'Series metadata', 'recorded' => true],
                ['service' => 'gluetun', 'destination' => '', 'purpose' => 'Nothing of its own', 'recorded' => true],
                ['service' => 'my-fork', 'destination' => 'unknown', 'purpose' => 'no record of what this service reaches', 'recorded' => false],
            ],
        ],
    ];
}

/**
 * The payload a stack sending nothing sends.
 *
 * @return array<string, mixed>
 */
function whatAStackSendingNothingSays(): array
{
    return ['api_version' => 1, 'kind' => 'outbound', 'data' => ['ours' => [], 'theirs' => []]];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Outgoing>
 */
function everyWayOfAskingWhatLeaves(MockResponse $answered, ?Obstacle $why = null, ?WhatLeavesThisMachine $leaving = null): array
{
    return [
        'the fake' => static fn(): Outgoing => $why instanceof Obstacle
            ? AStackThatSaysWhatLeavesIt::met($why)
            : AStackThatSaysWhatLeavesIt::with($leaving ?? theSameConnections()),
        'the adapter' => static function () use ($answered): Outgoing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Lookouts(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatLeavingTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything that leaves, folded to one line, so two answers can be compared. */
function everythingThatLeaves(Outgoing $outgoing): string
{
    return $outgoing->leaving(aStackThatSaysWhatItSends(), Session::of('a-session-not-a-secret'))->either(
        leaving: static function (WhatLeavesThisMachine $leaving): WhatLeavingTurnedOutToSay {
            $ours = [];

            foreach ($leaving->ours() as $request) {
                $ours[] = sprintf('%s[%s]%s/%s/%s/%s/%s', $request->asksFor()->value, implode(',', iterator_to_array($request->destinations(), preserve_keys: false)), $request->purpose(), $request->sends(), $request->allowed()->value, $request->switch(), $request->cost());
            }

            $theirs = [];

            foreach ($leaving->theirs() as $request) {
                $theirs[] = sprintf('%s:%s', $request->service()->named(), $request->reaches(
                    recorded: static fn(string $destination, string $purpose): WhatLeavingTurnedOutToSay => new WhatLeavingTurnedOutToSay(sprintf('%s/%s', $destination, $purpose)),
                    unrecorded: static fn(): WhatLeavingTurnedOutToSay => new WhatLeavingTurnedOutToSay('unrecorded'),
                )->said);
            }

            return new WhatLeavingTurnedOutToSay(sprintf('ours: %s || theirs: %s', implode(' | ', $ours), implode(' | ', $theirs)));
        },
        met: static fn(Obstacle $why): WhatLeavingTurnedOutToSay => new WhatLeavingTurnedOutToSay($why->value),
    )->said;
}

it('N10-R1, N10-R2, N10-R3 — comes away with lemonfiber\'s requests and its services\', apart', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysLeavesIt()));

    foreach (everyWayOfAskingWhatLeaves($answered) as $which => $make) {
        expect(everythingThatLeaves($make()))->toBe(
            'ours: registry[ghcr.io,lscr.io]To fetch the images the stack runs/The name and version of each image/allowed/registry.pull/No service can be installed or updated'
            . ' | updates[]To say when a newer lemonfiber is out/Nothing but the request itself/switched_off/updates.check/Nobody hears that a version came out'
            . ' || theirs: sonarr:thetvdb.com/Series metadata | gluetun:/Nothing of its own | my-fork:unrecorded',
            $which,
        );
    }
});

it('N10-R12 — a stack sending nothing answers with two empty lists, not an obstacle', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSendingNothingSays()));

    foreach (everyWayOfAskingWhatLeaves($answered, leaving: WhatLeavesThisMachine::of(OurRequests::of(), TheirRequests::of())) as $which => $make) {
        expect(everythingThatLeaves($make()))->toBe('ours:  || theirs: ', $which);
    }
});

it('N10-R12 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatLeaves($answered, $why) as $which => $make) {
            expect(everythingThatLeaves($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N10-R12 — connections this app cannot read are an obstacle, not a shorter list', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysLeavesIt('telemetry')))]);

    expect(everythingThatLeaves(new Lookouts(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('OutboundEnvelope', whatAStackSaysLeavesIt()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('OutboundEnvelope', whatAStackSendingNothingSays()))
        ->toBe([], "The empty payload this suite stands in for a stack with is not one a stack would send.\n");
});
