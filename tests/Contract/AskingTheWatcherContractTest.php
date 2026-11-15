<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AHeartbeat;
use Modules\Kernel\Api\AMonitor;
use Modules\Kernel\Api\AMonitorNotSetUp;
use Modules\Kernel\Api\AskingTheWatcher;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfMonitor;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAMonitorCovers;
use Modules\Kernel\Api\WhatKindOfThingIsCovered;
use Modules\Kernel\Api\WhatNoMonitorCovers;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WhichWayItLooks;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Watchers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatItsWatcherSees;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingTheWatcher contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** When the stack in these tests asked its watcher. */
const WHEN_THE_WATCHER_WAS_ASKED = 1_790_000_000;

/** The stack whose watcher is asked. */
function aStackWithAWatcher(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with: a watcher holding a monitor down, a heartbeat gone quiet and one left unconfigured. */
function theSameWatch(): WhatTheWatcherSees
{
    return WhatTheWatcherSees::answering(
        'uptime-kuma',
        Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED),
        WhatNoMonitorCovers::these(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::EntryPoint, 'jellyfin.home.arpa')),
        AMonitor::named('Sonarr', KindOfMonitor::Http, HowAMonitorStands::Down, Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED - 600), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED - 30)))
            ->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr'))
            ->reachedBy(WhichWayItLooks::Internal),
        AMonitor::named('Backups', KindOfMonitor::Heartbeat, HowAMonitorStands::NotReporting, Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED - 7_200), WhenItSays::unsaid())
            ->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Job, 'backup'))
            ->waitingFor(AHeartbeat::due(HowLong::ofSeconds(3_600), HowLong::ofSeconds(300), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED - 9_000)))),
        AMonitor::named('Front door', KindOfMonitor::Keyword, HowAMonitorStands::Up, Instant::atEpochSeconds(WHEN_THE_WATCHER_WAS_ASKED - 60), WhenItSays::unsaid())
            ->switchedOff()
            ->notSetUp(AMonitorNotSetUp::said('the sign-in page', 'the home page', 'The sign-in page answers only over the tunnel')),
    );
}

/**
 * The payload a stack sends for that watcher.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysItsWatcherSees(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'watcher',
        'data' => [
            'watcher' => 'uptime-kuma',
            'reachable' => true,
            'read_at' => WHEN_THE_WATCHER_WAS_ASKED,
            'monitors' => [
                ['name' => 'Sonarr', 'kind' => 'http', 'state' => 'down', 'since' => WHEN_THE_WATCHER_WAS_ASKED - 600, 'active' => true, 'last_reading' => WHEN_THE_WATCHER_WAS_ASKED - 30, 'covers' => ['service' => 'sonarr'], 'path' => 'internal'],
                ['name' => 'Backups', 'kind' => 'heartbeat', 'state' => 'not-reporting', 'since' => WHEN_THE_WATCHER_WAS_ASKED - 7_200, 'active' => true, 'covers' => ['job' => 'backup'], 'heartbeat' => ['every_s' => 3_600, 'grace_s' => 300, 'last_check_in' => WHEN_THE_WATCHER_WAS_ASKED - 9_000]],
                ['name' => 'Front door', 'kind' => 'keyword', 'state' => 'up', 'since' => WHEN_THE_WATCHER_WAS_ASKED - 60, 'active' => false, 'unconfigured' => ['wanted' => 'the sign-in page', 'watches' => 'the home page', 'why' => 'The sign-in page answers only over the tunnel']],
            ],
            'uncovered' => [['entry-point' => 'jellyfin.home.arpa']],
            ...$changed,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingTheWatcher>
 */
function everyWayOfAskingTheWatcher(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingTheWatcher => $why instanceof Obstacle
            ? AStackThatSaysWhatItsWatcherSees::met($why)
            : AStackThatSaysWhatItsWatcherSees::with(theSameWatch()),
        'the adapter' => static function () use ($answered): AskingTheWatcher {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Watchers(new PinnedClients());
        },
    ];
}

/** A moment the stack named, or that it named none. */
function aWatchMoment(WhenItSays $when): string
{
    return $when->either(
        at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()),
        unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

/** What a monitor covers, folded to one word. */
function aCoverSaid(WhatAMonitorCovers $covers): string
{
    return sprintf('%s:%s', $covers->kind()->value, $covers->name());
}

/** One monitor, folded to one line. */
function aMonitorSaid(AMonitor $monitor): string
{
    return sprintf(
        '%s %s %s since %d %s read %s covers %s by %s heartbeat %s unconfigured %s',
        $monitor->name(),
        $monitor->kind()->value,
        $monitor->stands()->value,
        $monitor->since()->epochSeconds(),
        $monitor->isActive() ? 'on' : 'off',
        aWatchMoment($monitor->lastReading()),
        $monitor->covers(
            thing: static fn(WhatAMonitorCovers $covers): TheWordCarriedOut => new TheWordCarriedOut(aCoverSaid($covers)),
            nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        )->said,
        $monitor->reached(
            by: static fn(WhichWayItLooks $path): TheWordCarriedOut => new TheWordCarriedOut($path->value),
            unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
        )->said,
        $monitor->heartbeat(
            waits: static fn(AHeartbeat $heartbeat): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d/%d last %s missing from %s', $heartbeat->every()->inSeconds(), $heartbeat->grace()->inSeconds(), aWatchMoment($heartbeat->lastCheckIn()), aWatchMoment($heartbeat->missingFrom()))),
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        )->said,
        $monitor->setUp(
            instead: static fn(AMonitorNotSetUp $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('[%s|%s|%s]', $why->wanted(), $why->watches(), $why->why())),
            asMeant: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no'),
        )->said,
    );
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheWatcherSays(AskingTheWatcher $asking): string
{
    return $asking->whatItSees(aStackWithAWatcher(), Session::of('a-session-not-a-secret'))->either(
        said: static function (WhatTheWatcherSees $sees): TheWordCarriedOut {
            $monitors = [];

            foreach ($sees as $monitor) {
                $monitors[] = aMonitorSaid($monitor);
            }

            $uncovered = [];

            foreach ($sees->uncovered() as $covers) {
                $uncovered[] = aCoverSaid($covers);
            }

            return new TheWordCarriedOut(sprintf(
                'watcher "%s" %s at %d; uncovered [%s]; %s',
                $sees->watcher(),
                $sees->answers() ? 'answers' : sprintf('blind (%s)', $sees->blindBecause()),
                $sees->readAt()->epochSeconds(),
                implode(',', $uncovered),
                implode('; ', $monitors),
            ));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with every monitor, what each covers and how, and what nothing covers', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItsWatcherSees()));
    $said = [];

    foreach (everyWayOfAskingTheWatcher($answered) as $which => $make) {
        $said[$which] = everythingTheWatcherSays($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe(sprintf(
            'watcher "uptime-kuma" answers at %d; uncovered [entry-point:jellyfin.home.arpa]; Sonarr http down since %d on read %d covers service:sonarr by internal heartbeat none unconfigured no; Backups heartbeat not-reporting since %d on read unsaid covers job:backup by unsaid heartbeat 3600/300 last %d missing from %d unconfigured no; Front door keyword up since %d off read unsaid covers nothing by unsaid heartbeat none unconfigured [the sign-in page|the home page|The sign-in page answers only over the tunnel]',
            WHEN_THE_WATCHER_WAS_ASKED,
            WHEN_THE_WATCHER_WAS_ASKED - 600,
            WHEN_THE_WATCHER_WAS_ASKED - 30,
            WHEN_THE_WATCHER_WAS_ASKED - 7_200,
            WHEN_THE_WATCHER_WAS_ASKED - 9_000,
            WHEN_THE_WATCHER_WAS_ASKED - 9_000 + 3_600 + 300,
            WHEN_THE_WATCHER_WAS_ASKED - 60,
        ));
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingTheWatcher($answered, $why) as $which => $make) {
            expect(everythingTheWatcherSays($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('reads a watcher that does not answer as blind, with the stack\'s reason and everything it leaves uncovered', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItsWatcherSees(['reachable' => false, 'unreachable_because' => 'Nothing fills the watcher capability', 'watcher' => null, 'monitors' => null])))]);

    expect(everythingTheWatcherSays(new Watchers(new PinnedClients())))->toBe(sprintf('watcher "" blind (Nothing fills the watcher capability) at %d; uncovered [entry-point:jellyfin.home.arpa]; ', WHEN_THE_WATCHER_WAS_ASKED));
});

it('reads a watcher that holds no monitors and leaves nothing uncovered', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'watcher', 'data' => ['reachable' => true, 'read_at' => WHEN_THE_WATCHER_WAS_ASKED, 'watcher' => 'uptime-kuma']]))]);

    expect(everythingTheWatcherSays(new Watchers(new PinnedClients())))->toBe(sprintf('watcher "uptime-kuma" answers at %d; uncovered []; ', WHEN_THE_WATCHER_WAS_ASKED));
});

it('reads a heartbeat that never arrived as missing from no moment', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItsWatcherSees(['monitors' => [['name' => 'Backups', 'kind' => 'heartbeat', 'state' => 'down', 'since' => 1, 'active' => true, 'heartbeat' => ['every_s' => 60, 'grace_s' => 0, 'last_check_in' => null]]]])))]);

    expect(everythingTheWatcherSays(new Watchers(new PinnedClients())))->toEndWith('heartbeat 60/0 last unsaid missing from unsaid unconfigured no');
});

// `watcher` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read, rather than drawing a monitor that stopped reporting as a service that is down', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItsWatcherSees($changed)))]);

    expect(everythingTheWatcherSays(new Watchers(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a state it has no case for' => [['monitors' => [['name' => 'A', 'kind' => 'http', 'state' => 'sideways', 'since' => 1, 'active' => true]]]],
    'a kind it has no case for' => [['monitors' => [['name' => 'A', 'kind' => 'smoke', 'state' => 'up', 'since' => 1, 'active' => true]]]],
    'a path it has no case for' => [['monitors' => [['name' => 'A', 'kind' => 'http', 'state' => 'up', 'since' => 1, 'active' => true, 'covers' => ['service' => 'sonarr'], 'path' => 'sideways']]]],
    'a cover under no key it reads' => [['monitors' => [['name' => 'A', 'kind' => 'http', 'state' => 'up', 'since' => 1, 'active' => true, 'covers' => ['host' => 'nas']]]]],
    'a cover that is not a name' => [['uncovered' => [['service' => 7]]]],
    'a cover that is not an object' => [['uncovered' => ['sonarr']]],
    'a monitor that is not an object' => [['monitors' => ['Sonarr']]],
    'a heartbeat below nothing' => [['monitors' => [['name' => 'A', 'kind' => 'heartbeat', 'state' => 'up', 'since' => 1, 'active' => true, 'heartbeat' => ['every_s' => -1, 'grace_s' => 0]]]]],
    'a monitor with no time' => [['monitors' => [['name' => 'A', 'kind' => 'http', 'state' => 'up', 'active' => true]]]],
    'a moment before the epoch' => [['read_at' => -1]],
    'a blind watcher with no reason' => [['reachable' => false]],
    'an unconfigured monitor with no reason' => [['monitors' => [['name' => 'A', 'kind' => 'http', 'state' => 'up', 'since' => 1, 'active' => true, 'unconfigured' => ['wanted' => 'a', 'watches' => 'b']]]]],
    'a heartbeat that is not an object' => [['monitors' => [['name' => 'A', 'kind' => 'heartbeat', 'state' => 'up', 'since' => 1, 'active' => true, 'heartbeat' => 60]]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('WatcherEnvelope', whatAStackSaysItsWatcherSees()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
