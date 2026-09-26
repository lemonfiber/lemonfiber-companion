<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowTheHandoverWent;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheFilesTouched;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatTheHandoverDid;
use Modules\Sdk\Api\Keepers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatHosts;
use Tests\Support\Fakes\SequencedEntropy;
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

            return new Keepers(new PinnedClients(), SequencedEntropy::counting());
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

    expect(everythingKeptBy(new Keepers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('N16-R5 — an unsupported machine with nothing to do instead is an obstacle', function (): void {
    // *Not available here* with no sentence beside it is the empty box that
    // reads as *off*. Refused at the reading rather than rendered, because a
    // screen cannot tell the two apart once the field is gone.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(
        (string) json_encode(whatAnUnsupportedMachineSends(saysWhatToDoInstead: false)),
    )]);

    expect(everythingKeptBy(new Keepers(new PinnedClients(), SequencedEntropy::counting())))->toBe(Obstacle::StackDidNotAnswer->value);
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

/**
 * The payload a machine answers an install or a removal with.
 *
 * The listing read after the act, carrying what the act changed. What changed
 * and the command's own row are parameters, each merged over a whole one, so a
 * case names the one thing it varies and the rest stays a payload a stack
 * would send.
 *
 * What is left out is named rather than cut out of the result afterwards:
 * `changed` or `commands` for a whole part, `changed.rehearsed` or
 * `row.output` for one field of the change or of the guard's row.
 *
 * @param array<string, mixed> $changed merged over an install that started the guard
 * @param array<string, mixed> $row     merged over the guard's own row
 *
 * @return array<string, mixed>
 */
function whatAHandoverSends(array $changed = [], array $row = [], string ...$leftOut): array
{
    $theRow = [
        'name' => 'watch',
        'command' => 'lemonfiber watch',
        'guarantees' => 'stops the stack if the data location disappears',
        'standing' => 'hosted',
        'definition' => '/home/me/.config/systemd/user/lemonfiber-watch.service',
        'runs' => '/usr/local/bin/lemonfiber watch media',
        'output' => '/home/me/.local/state/lemonfiber/hosted/watch.log',
        ...$row,
    ];
    $theChange = [
        'name' => 'watch',
        'installed' => true,
        'started' => true,
        'rehearsed' => false,
        'touched' => ['/home/me/.config/systemd/user/lemonfiber-watch.service'],
        ...$changed,
    ];
    $data = [
        'manager' => 'systemd',
        'caveat' => 'A user service runs while you are logged in.',
        'commands' => [
            [
                'name' => 'expiring',
                'command' => 'lemonfiber household expiring',
                'guarantees' => 'closes requests nobody has ruled on',
                'standing' => 'not-hosted',
            ],
            array_diff_key($theRow, array_flip(theLeftOutUnder('row', $leftOut))),
        ],
        'changed' => array_diff_key($theChange, array_flip(theLeftOutUnder('changed', $leftOut))),
    ];

    return ['api_version' => 1, 'kind' => 'hosting', 'data' => array_diff_key($data, array_flip($leftOut))];
}

/**
 * The fields left out beneath one part of a handover, from names like `row.output`.
 *
 * @param array<array-key, string> $leftOut
 *
 * @return list<string>
 */
function theLeftOutUnder(string $part, array $leftOut): array
{
    $under = [];

    foreach ($leftOut as $name) {
        if (str_starts_with($name, sprintf('%s.', $part))) {
            $under[] = substr($name, strlen($part) + 1);
        }
    }

    return $under;
}

/**
 * What the far end answers a handing over with, where it did it.
 *
 * @param array<string, mixed> $changed
 * @param array<string, mixed> $row
 */
function aHandoverAnswer(array $changed = [], array $row = []): MockResponse
{
    return MockResponse::make((string) json_encode(whatAHandoverSends($changed, $row)));
}

/**
 * Both ways of handing a command over, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Hosting>
 */
function everyWayOfHandingOver(MockResponse $answered, HowTheHandoverWent $went): array
{
    return [
        'the fake' => static fn(): Hosting => AStackThatHosts::with(theSameHosting(), $went),
        'the adapter' => static function () use ($answered): Hosting {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Keepers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** Where a handover says the command's words go, or the word for it not saying. */
function whereTheHandoverWrites(WhatTheHandoverDid $did): string
{
    return $did->writesTo(
        there: static fn(string $where): WhatTheHostingTurnedOutToSay => new WhatTheHostingTurnedOutToSay($where),
        unsaid: static fn(): WhatTheHostingTurnedOutToSay => new WhatTheHostingTurnedOutToSay('nowhere said'),
    )->said;
}

/**
 * Everything one handing over came to, as one line a case can compare.
 *
 * Every field, so a reader that dropped one — the rehearsal, above all — could
 * not pass by the others agreeing.
 */
function whatCameOfHandingOver(Hosting $hosting, HandingOver $doing = HandingOver::Install): string
{
    return $hosting->handOver(aStackThatKeepsThingsRunning(), theSessionHostingIsAskedWith(), HostingAgreed::to($doing, 'watch'))->either(
        did: static fn(WhatTheHandoverDid $did): WhatTheHostingTurnedOutToSay => new WhatTheHostingTurnedOutToSay(sprintf(
            '%s %s; rehearsed %s; started %s; %s; writes to %s; touched %s',
            $did->did()->value,
            $did->name(),
            $did->wasRehearsed() ? 'yes' : 'no',
            $did->started() ? 'yes' : 'no',
            $did->standing()->value,
            whereTheHandoverWrites($did),
            count($did->touched()) === 0 ? 'nothing' : implode(', ', iterator_to_array($did->touched(), preserve_keys: false)),
        )),
        refused: static fn(string $said): WhatTheHostingTurnedOutToSay => new WhatTheHostingTurnedOutToSay(sprintf('refused: %s', $said)),
        met: static fn(Obstacle $why): WhatTheHostingTurnedOutToSay => new WhatTheHostingTurnedOutToSay($why->value),
    )->said;
}

/**
 * What the adapter makes of one body, sent as a stack would send it.
 *
 * @param array<string, mixed> $body
 */
function whatTheAdapterMakesOf(array $body, HandingOver $doing = HandingOver::Install): string
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode($body))]);

    return whatCameOfHandingOver(new Keepers(new PinnedClients(), SequencedEntropy::counting()), $doing);
}

it('an install says whether it started, where its words go, where it stands, and every file it wrote', function (): void {
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::installing(
        name: 'watch',
        rehearsed: false,
        started: true,
        standing: HowItIsHosted::Hosted,
        output: '/home/me/.local/state/lemonfiber/hosted/watch.log',
        touched: TheFilesTouched::these('/home/me/.config/systemd/user/lemonfiber-watch.service'),
    ));

    foreach (everyWayOfHandingOver(aHandoverAnswer(), $went) as $which => $make) {
        expect(whatCameOfHandingOver($make()))->toBe(
            'install watch; rehearsed no; started yes; hosted; writes to /home/me/.local/state/lemonfiber/hosted/watch.log; '
            . 'touched /home/me/.config/systemd/user/lemonfiber-watch.service',
            $which,
        );
    }
});

it('an install reports the standing the stack read afterwards, not that it was installed', function (): void {
    // Installed and not confirmed running is its own word, and nothing started.
    // A reader that took the act for the answer would call this one hosted.
    $answered = aHandoverAnswer(['started' => false], ['standing' => 'installed-unverified']);
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::installing(
        name: 'watch',
        rehearsed: false,
        started: false,
        standing: HowItIsHosted::InstalledUnverified,
        output: '/home/me/.local/state/lemonfiber/hosted/watch.log',
        touched: TheFilesTouched::these('/home/me/.config/systemd/user/lemonfiber-watch.service'),
    ));

    foreach (everyWayOfHandingOver($answered, $went) as $which => $make) {
        expect(whatCameOfHandingOver($make()))->toStartWith('install watch; rehearsed no; started no; installed-unverified;', $which);
    }
});

it('an install whose stack does not say where its words go says so, whether it sent null or nothing', function (): void {
    expect(whatTheAdapterMakesOf(whatAHandoverSends(row: ['output' => null])))->toContain('writes to nowhere said')
        ->and(whatTheAdapterMakesOf(whatAHandoverSends([], [], 'row.output')))->toContain('writes to nowhere said');
});

it('a removal names every file it took back, and started nothing', function (): void {
    $answered = aHandoverAnswer(
        ['installed' => false, 'started' => false, 'touched' => ['/home/me/a.service', '/home/me/b.timer']],
        ['standing' => 'not-hosted'],
    );
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::removing(
        name: 'watch',
        rehearsed: false,
        standing: HowItIsHosted::NotHosted,
        touched: TheFilesTouched::these('/home/me/a.service', '/home/me/b.timer'),
    ));

    foreach (everyWayOfHandingOver($answered, $went) as $which => $make) {
        expect(whatCameOfHandingOver($make(), HandingOver::Remove))->toBe(
            'remove watch; rehearsed no; started no; not-hosted; writes to nowhere said; touched /home/me/a.service, /home/me/b.timer',
            $which,
        );
    }
});

it('removing what was never hosted is an answer with nothing taken back', function (): void {
    $answered = aHandoverAnswer(['installed' => false, 'started' => false, 'touched' => []], ['standing' => 'not-hosted']);
    $went = HowTheHandoverWent::did(WhatTheHandoverDid::removing(name: 'watch', rehearsed: false, standing: HowItIsHosted::NotHosted, touched: TheFilesTouched::these()));

    foreach (everyWayOfHandingOver($answered, $went) as $which => $make) {
        expect(whatCameOfHandingOver($make(), HandingOver::Remove))->toEndWith('touched nothing', $which);
    }
});

it('a rehearsal is carried as one, for an install and for a removal', function (): void {
    expect(whatTheAdapterMakesOf(whatAHandoverSends(['rehearsed' => true, 'started' => false, 'touched' => []])))
        ->toStartWith('install watch; rehearsed yes; started no;')
        ->and(whatTheAdapterMakesOf(whatAHandoverSends(['rehearsed' => true, 'installed' => false, 'started' => false]), HandingOver::Remove))
        ->toStartWith('remove watch; rehearsed yes; started no;');
});

it('carries the stack\'s own words where it answered and would not', function (): void {
    // A guard asked to guard nothing, answered with an `error` envelope. The
    // machine said why, which is a different thing to show from a machine that
    // never answered.
    $refusal = MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'error',
        'data' => [
            'code' => 'LF-HOST-003',
            'severity' => 'error',
            'state' => 'guided',
            'summary' => 'The guard was not told what to guard',
            'meaning' => 'A guard stops the forms it was started against.',
            'remedy' => ['summary' => 'Name the forms to guard'],
        ],
    ]), 400);

    foreach (everyWayOfHandingOver($refusal, HowTheHandoverWent::refused('The guard was not told what to guard')) as $which => $make) {
        expect(whatCameOfHandingOver($make()))->toBe('refused: The guard was not told what to guard', $which);
    }
});

it('tells a refused session, and a stack that said nothing, from a refusal in words', function (): void {
    $table = [
        [MockResponse::make('the session is not one', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfHandingOver($answered, HowTheHandoverWent::met($why)) as $which => $make) {
            expect(whatCameOfHandingOver($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('an account of an act this app cannot read is a stack that did not answer', function (): void {
    // Every one of these is refused rather than defaulted. A missing
    // `rehearsed` read as false is a rehearsal shown as a real install; a
    // missing standing is an install reported by nothing.
    $spoiled = [
        'no account' => whatAHandoverSends([], [], 'changed'),
        'no rehearsal' => whatAHandoverSends([], [], 'changed.rehearsed'),
        'a rehearsal that is not yes or no' => whatAHandoverSends(['rehearsed' => 'no']),
        'no name for what it acted on' => whatAHandoverSends([], [], 'changed.name'),
        'no direction' => whatAHandoverSends([], [], 'changed.installed'),
        'no start' => whatAHandoverSends([], [], 'changed.started'),
        'no files' => whatAHandoverSends([], [], 'changed.touched'),
        'no listing' => whatAHandoverSends([], [], 'commands'),
        'no standing' => whatAHandoverSends([], [], 'row.standing'),
        'no name on the row' => whatAHandoverSends([], [], 'row.name'),
        'no name' => whatAHandoverSends(['name' => ' ']),
        'a name nothing lists' => whatAHandoverSends(['name' => 'boot']),
        'a direction that is not yes or no' => whatAHandoverSends(['installed' => 'yes']),
        'a start that is not yes or no' => whatAHandoverSends(['started' => 1]),
        'files that are not a list' => whatAHandoverSends(['touched' => 'a.service']),
        'a blank file' => whatAHandoverSends(['touched' => ['a.service', ' ']]),
        'a removal that started something' => whatAHandoverSends(['installed' => false, 'started' => true]),
        'a standing nobody reads' => whatAHandoverSends(row: ['standing' => 'running']),
        'a standing that is not a word' => whatAHandoverSends(row: ['standing' => 7]),
        'somewhere to write that is no path' => whatAHandoverSends(row: ['output' => ' ']),
        'somewhere to write that is not text' => whatAHandoverSends(row: ['output' => 42]),
    ];

    foreach ($spoiled as $which => $body) {
        expect(whatTheAdapterMakesOf($body))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('a reading with no account of an act is not taken for one', function (): void {
    // The listing on its own answers what is hosted, not what was done.
    expect(whatTheAdapterMakesOf(whatAHostingMachineSends()))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a machine answering a handing over with payloads the contract would accept', function (): void {
    $payloads = [
        'an install' => whatAHandoverSends(),
        'a removal' => whatAHandoverSends(['installed' => false, 'started' => false], ['standing' => 'not-hosted', 'output' => null]),
        'a rehearsal' => whatAHandoverSends(['rehearsed' => true, 'started' => false, 'touched' => []]),
    ];

    foreach ($payloads as $which => $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('HostingEnvelope', $payload))
            ->toBe([], sprintf("The payload this suite stands in for %s with is not one a stack would send.\n", $which));
    }
});
