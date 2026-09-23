<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Sdk\Api\Keepers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatHosts;
use Tests\Support\WhatTheContractAccepts;

// The Hosting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `StallingContractTest`'s argument one endpoint along: every
// test of the screen showing what survives a reboot will hand its subject an
// `AStackThatHosts` and never open a socket, so a fake easier to satisfy than
// the adapter would enforce *a command that did not come back is named* against
// a stack that always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing, so a contract asking
// those would either fail on it or be weakened to pass.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose unattended commands are asked after. */
function aStackThatKeepsThingsRunning(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionHostingIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What both implementations answer with, where the machine has a manager. */
function theSameHosting(): WhatRunsUnattended
{
    return WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Launchd,
        Unattended::called('Watching the library', 'lemonfiber watch --all', 'New files are noticed', HowItIsHosted::Hosted),
        Unattended::orphaned('Seeding what you share', 'lemonfiber seed', 'Torrents keep seeding', '/usr/local/bin/lemonfiber'),
    );
}

/**
 * The payload a machine with a launch agent sends.
 *
 * Separate from the response so a reader can see the same array the adapter is
 * given. A fixture checked in one place and sent in another is a fixture that
 * can drift from itself.
 *
 * The second row's standing is a parameter rather than something a case reaches
 * in and overwrites. One fixture with one named variation says *this and only
 * this is different*; array surgery on a payload says it too, and says it where
 * nothing can check the path it reached through still exists.
 *
 * @return array<string, mixed>
 */
function whatAHostingMachineSends(string $standingOfTheSecond = 'orphaned'): array
{
    return [
        'api_version' => 1,
        'kind' => 'hosting',
        'data' => [
            'manager' => 'launchd',
            'commands' => [
                [
                    'name' => 'Watching the library',
                    'command' => 'lemonfiber watch --all',
                    'guarantees' => 'New files are noticed',
                    'standing' => 'hosted',
                ],
                [
                    'name' => 'Seeding what you share',
                    'command' => 'lemonfiber seed',
                    'guarantees' => 'Torrents keep seeding',
                    'standing' => $standingOfTheSecond,
                    'missing' => '/usr/local/bin/lemonfiber',
                ],
            ],
        ],
    ];
}

/**
 * The payload a machine this product cannot configure sends.
 *
 * Whether it says what to do instead is a parameter, for the reason above: the
 * case that matters is the one where it does not, and naming that here keeps
 * the two bodies one fixture apart rather than two fixtures that can drift.
 *
 * @return array<string, mixed>
 */
function whatAnUnsupportedMachineSends(bool $saysWhatToDoInstead = true): array
{
    $data = [
        'manager' => 'unsupported',
        'commands' => [
            [
                'name' => 'Watching the library',
                'command' => 'lemonfiber watch --all',
                'guarantees' => 'New files are noticed',
                'standing' => 'unsupported',
            ],
        ],
    ];

    if ($saysWhatToDoInstead) {
        $data['instruction'] = 'Add it to your own login items.';
    }

    return ['api_version' => 1, 'kind' => 'hosting', 'data' => $data];
}

/** What the far end answers where the machine has a launch agent. */
function aHostingAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAHostingMachineSends()));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Hosting>
 */
function everyWayOfAskingWhatIsKept(
    MockResponse $answered,
    ?Obstacle $why = null,
    ?WhatRunsUnattended $running = null,
): array {
    return [
        'the fake' => static fn(): Hosting => $why instanceof Obstacle
            ? AStackThatHosts::met($why)
            : AStackThatHosts::with($running ?? theSameHosting()),
        'the adapter' => static function () use ($answered): Hosting {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Keepers(new PinnedClients());
        },
    ];
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheHostingTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Every command, folded to a word each, so an order can be compared. */
function everythingKeptBy(Hosting $hosting): string
{
    return $hosting->keptRunningOn(aStackThatKeepsThingsRunning(), theSessionHostingIsAskedWith())->either(
        keeps: static function (WhatRunsUnattended $running): WhatTheHostingTurnedOutToSay {
            $rows = [];

            foreach ($running as $one) {
                $rows[] = sprintf('%s/%s/%s', $one->name(), $one->command(), $one->standing()->value);
            }

            return new WhatTheHostingTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatTheHostingTurnedOutToSay
            => new WhatTheHostingTurnedOutToSay($why->value),
    )->said;
}

/** What keeps the machine's commands running, whichever arm the answer took. */
function whatKeepsThemRunningIn(Hosting $hosting): string
{
    return $hosting->keptRunningOn(aStackThatKeepsThingsRunning(), theSessionHostingIsAskedWith())->either(
        keeps: static fn(WhatRunsUnattended $running): WhatTheHostingTurnedOutToSay
            => new WhatTheHostingTurnedOutToSay($running->whatKeepsThem()->value),
        met: static fn(Obstacle $why): WhatTheHostingTurnedOutToSay
            => new WhatTheHostingTurnedOutToSay($why->value),
    )->said;
}

/** What to do instead, or the word for the machine doing it itself. */
function whatToDoInsteadIn(Hosting $hosting): string
{
    return $hosting->keptRunningOn(aStackThatKeepsThingsRunning(), theSessionHostingIsAskedWith())->either(
        keeps: static fn(WhatRunsUnattended $running): WhatTheHostingTurnedOutToSay
            => $running->whereItCannot(
                instead: static fn(string $what): WhatTheHostingTurnedOutToSay
                    => new WhatTheHostingTurnedOutToSay($what),
                itself: static fn(): WhatTheHostingTurnedOutToSay
                    => new WhatTheHostingTurnedOutToSay('the machine does this itself'),
            ),
        met: static fn(Obstacle $why): WhatTheHostingTurnedOutToSay
            => new WhatTheHostingTurnedOutToSay($why->value),
    )->said;
}

/** The names of what did not come back, in the stack's order. */
function whatDidNotComeBackIn(Hosting $hosting): string
{
    return $hosting->keptRunningOn(aStackThatKeepsThingsRunning(), theSessionHostingIsAskedWith())->either(
        keeps: static function (WhatRunsUnattended $running): WhatTheHostingTurnedOutToSay {
            $names = [];

            foreach ($running->didNotComeBack() as $one) {
                $names[] = $one->name();
            }

            return new WhatTheHostingTurnedOutToSay(implode(' | ', $names));
        },
        met: static fn(Obstacle $why): WhatTheHostingTurnedOutToSay
            => new WhatTheHostingTurnedOutToSay($why->value),
    )->said;
}

it('N16-R5 — comes away with every command, what it is, and where it stands', function (): void {
    // All of them, hosted or not, in the stack's order. A listing of only the
    // installed ones would answer the question nobody asks.
    foreach (everyWayOfAskingWhatIsKept(aHostingAnswer()) as $which => $make) {
        expect(everythingKeptBy($make()))->toBe(
            'Watching the library/lemonfiber watch --all/hosted | Seeding what you share/lemonfiber seed/orphaned',
            $which,
        );
    }
});

it('N16-R5 — says what keeps them running, which is a fact about the machine', function (): void {
    foreach (everyWayOfAskingWhatIsKept(aHostingAnswer()) as $which => $make) {
        expect(whatKeepsThemRunningIn($make()))->toBe('launchd', $which)
            ->and(whatToDoInsteadIn($make()))->toBe('the machine does this itself', $which);
    }
});

it('N16-R5 — a machine this product cannot configure carries what to do instead', function (): void {
    // The arm that must not render as *off*. Both implementations carry the
    // sentence, so a fake that shrugged at it could not be used to build a
    // screen that draws an empty box where the instruction belongs.
    $answered = MockResponse::make((string) json_encode(whatAnUnsupportedMachineSends()));
    $running = WhatRunsUnattended::unsupported(
        'Add it to your own login items.',
        Unattended::called('Watching the library', 'lemonfiber watch --all', 'New files are noticed', HowItIsHosted::Unsupported),
    );

    foreach (everyWayOfAskingWhatIsKept($answered, running: $running) as $which => $make) {
        expect(whatKeepsThemRunningIn($make()))->toBe('unsupported', $which)
            ->and(whatToDoInsteadIn($make()))->toBe('Add it to your own login items.', $which);
    }
});

it('N16-R6 — names what did not come back, and the orphan is in it', function (): void {
    foreach (everyWayOfAskingWhatIsKept(aHostingAnswer()) as $which => $make) {
        expect(whatDidNotComeBackIn($make()))->toBe('Seeding what you share', $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatIsKept($answered, $why) as $which => $make) {
            expect(everythingKeptBy($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N16-R13 — a listing this app cannot read is an obstacle, not a shorter list', function (): void {
    // The direction of error that matters. A row dropped for being unreadable
    // is one fewer command shown as not coming back, and an operator reading a
    // short list concludes the reboot went better than it did.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(
        (string) json_encode(whatAHostingMachineSends('a-word-this-app-does-not-read')),
    )]);

    expect(everythingKeptBy(new Keepers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('N16-R5 — an unsupported machine with nothing to do instead is an obstacle', function (): void {
    // *Not available here* with no sentence beside it is the empty box that
    // reads as *off*. Refused at the reading rather than rendered, because a
    // screen cannot tell the two apart once the field is gone.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(
        (string) json_encode(whatAnUnsupportedMachineSends(saysWhatToDoInstead: false)),
    )]);

    expect(everythingKeptBy(new Keepers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a machine with a payload the contract would accept', function (): void {
    // Both bodies, because the two arms are different shapes: one carries an
    // instruction and no manager this product configures, and the other carries
    // neither. A suite judging only the first would be judging the arm the
    // reader takes least often.
    expect(WhatTheContractAccepts::complaintsAbout('HostingEnvelope', whatAHostingMachineSends()))
        ->toBe([], "The payload this suite stands in for a machine with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('HostingEnvelope', whatAnUnsupportedMachineSends()))
        ->toBe([], "The unsupported payload this suite stands in for a machine with is not one a stack would send.\n");
});
