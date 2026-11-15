<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadInTheQueue;
use Modules\Kernel\Api\AHealingAct;
use Modules\Kernel\Api\AHealingRun;
use Modules\Kernel\Api\AnActTaken;
use Modules\Kernel\Api\AnItemActedOn;
use Modules\Kernel\Api\AServiceHealingCouldNotReach;
use Modules\Kernel\Api\AskingTheQueue;
use Modules\Kernel\Api\AWedgedItem;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfAnAct;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WhetherHealingActs;
use Modules\Kernel\Api\WhyItIsWedged;
use Modules\Sdk\Api\Healers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysHowItsQueueHeals;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingTheQueue contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** When the queue in these tests was last classified. */
const WHEN_THE_QUEUE_WAS_CLASSIFIED = 1_790_000_000;

/** The stack whose queue is asked. */
function aStackWithAQueue(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with: two unhealthy items, a curator out of reach, a run that took three acts and a rehearsal. */
function theSameHealing(): HowTheQueueHeals
{
    return HowTheQueueHeals::switched(WhetherHealingActs::On, 3, HowLong::ofSeconds(1_800), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_WAS_CLASSIFIED)), '')
        ->finding(
            AWedgedItem::flagged(ADownloadInTheQueue::heldBy(ServiceId::called('sonarr'), 'Show.S01E01.1080p', 'Show'), WhyItIsWedged::Stalled, 'No seed for an hour', 2)
                ->added(WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_WAS_CLASSIFIED - 7_200)))
                ->nextWould(AHealingAct::Remove, AHealingAct::Blocklist, AHealingAct::Search),
            AWedgedItem::flagged(ADownloadInTheQueue::heldBy(ServiceId::called('qbittorrent'), 'Unknown.Release', ''), WhyItIsWedged::Orphaned, 'In no curator\'s queue', 1)
                ->leftAloneUntil(WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_WAS_CLASSIFIED + 600))),
        )
        ->notReaching(AServiceHealingCouldNotReach::because(ServiceId::called('radarr'), 'It refused the key'))
        ->after(
            AHealingRun::ranAt(
                Instant::atEpochSeconds(WHEN_THE_QUEUE_WAS_CLASSIFIED - 300),
                AnItemActedOn::because(
                    ADownloadInTheQueue::heldBy(ServiceId::called('sonarr'), 'Old.Release', 'Old'),
                    'Failed three times',
                    AnActTaken::took(AHealingAct::Remove, WhatBecameOfAnAct::Done, ''),
                    AnActTaken::took(AHealingAct::Blocklist, WhatBecameOfAnAct::FellBack, 'The curator keeps no blocklist')->inPlaceOf(AHealingAct::Remove),
                    AnActTaken::took(AHealingAct::Search, WhatBecameOfAnAct::Refused, 'No indexer answered'),
                ),
            ),
            AHealingRun::rehearsedAt(Instant::atEpochSeconds(WHEN_THE_QUEUE_WAS_CLASSIFIED - 3_600)),
        );
}

/**
 * The payload a stack sends for that queue.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsQueue(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'queue',
        'data' => [
            'healing' => 'on',
            'strikes_needed' => 3,
            'grace_s' => 1_800,
            'incomplete' => false,
            'rehearsed' => false,
            'classified_at' => WHEN_THE_QUEUE_WAS_CLASSIFIED,
            'items' => [
                ['service' => 'sonarr', 'release' => 'Show.S01E01.1080p', 'title' => 'Show', 'class' => 'stalled', 'why' => 'No seed for an hour', 'strikes' => 2, 'added_at' => WHEN_THE_QUEUE_WAS_CLASSIFIED - 7_200, 'next' => ['remove', 'blocklist', 'search']],
                ['service' => 'qbittorrent', 'release' => 'Unknown.Release', 'class' => 'orphaned', 'why' => 'In no curator\'s queue', 'strikes' => 1, 'in_grace_until' => WHEN_THE_QUEUE_WAS_CLASSIFIED + 600, 'next' => null],
            ],
            'unreachable' => [['service' => 'radarr', 'why' => 'It refused the key']],
            'runs' => [
                ['at' => WHEN_THE_QUEUE_WAS_CLASSIFIED - 300, 'rehearsed' => false, 'items' => [
                    ['service' => 'sonarr', 'release' => 'Old.Release', 'title' => 'Old', 'why' => 'Failed three times', 'acts' => [
                        ['act' => 'remove', 'outcome' => 'done'],
                        ['act' => 'blocklist', 'outcome' => 'fell-back', 'instead' => 'remove', 'why' => 'The curator keeps no blocklist'],
                        ['act' => 'search', 'outcome' => 'refused', 'why' => 'No indexer answered'],
                    ]],
                ]],
                ['at' => WHEN_THE_QUEUE_WAS_CLASSIFIED - 3_600, 'rehearsed' => true],
            ],
            ...$changed,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingTheQueue>
 */
function everyWayOfAskingTheQueue(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingTheQueue => $why instanceof Obstacle
            ? AStackThatSaysHowItsQueueHeals::met($why)
            : AStackThatSaysHowItsQueueHeals::with(theSameHealing()),
        'the adapter' => static function () use ($answered): AskingTheQueue {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Healers(new PinnedClients());
        },
    ];
}

/** A moment the stack named, or that it named none. */
function aQueueMoment(WhenItSays $when): string
{
    return $when->either(
        at: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut((string) $at->epochSeconds()),
        unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

/** A download, folded to one word. */
function aDownloadSaid(ADownloadInTheQueue $download): string
{
    return sprintf('%s/%s/%s', $download->service()->named(), $download->release(), $download->title());
}

/** An unhealthy item, folded to one line. */
function aWedgedItemSaid(AWedgedItem $item): string
{
    $next = [];

    foreach ($item->next() as $act) {
        $next[] = $act->value;
    }

    return sprintf(
        '%s %s (%s) %d strikes added %s grace %s next [%s]',
        aDownloadSaid($item->download()),
        $item->class()->value,
        $item->why(),
        $item->strikes(),
        aQueueMoment($item->whenAdded()),
        aQueueMoment($item->graceEnds()),
        implode(',', $next),
    );
}

/** A run, folded to one line. */
function aRunSaid(AHealingRun $run): string
{
    $items = [];

    foreach ($run as $item) {
        $acts = [];

        foreach ($item as $act) {
            $acts[] = sprintf(
                '%s=%s%s%s',
                $act->act()->value,
                $act->outcome()->value,
                $act->insteadOf(
                    instead: static fn(AHealingAct $instead): TheWordCarriedOut => new TheWordCarriedOut(sprintf(' instead %s', $instead->value)),
                    nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut(''),
                )->said,
                $act->why() === '' ? '' : sprintf(' (%s)', $act->why()),
            );
        }

        $items[] = sprintf('%s (%s) [%s]', aDownloadSaid($item->download()), $item->why(), implode(', ', $acts));
    }

    return sprintf('%s at %d: %s', $run->wasRehearsed() ? 'rehearsal' : 'run', $run->at()->epochSeconds(), implode('; ', $items));
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheQueueSays(AskingTheQueue $asking): string
{
    return $asking->howItHeals(aStackWithAQueue(), Session::of('a-session-not-a-secret'))->either(
        said: static function (HowTheQueueHeals $queue): TheWordCarriedOut {
            $items = [];

            foreach ($queue as $item) {
                $items[] = aWedgedItemSaid($item);
            }

            $unreachable = [];

            foreach ($queue->unreachable() as $service) {
                $unreachable[] = sprintf('%s (%s)', $service->service()->named(), $service->why());
            }

            $runs = [];

            foreach ($queue->runs() as $run) {
                $runs[] = aRunSaid($run);
            }

            return new TheWordCarriedOut(sprintf(
                'healing %s after %d strikes, grace %ds, classified %s, mismatch "%s"%s%s; items {%s}; unreachable {%s}; runs {%s}',
                $queue->healing()->value,
                $queue->strikesNeeded(),
                $queue->grace()->inSeconds(),
                aQueueMoment($queue->classifiedAt()),
                $queue->mismatch(),
                $queue->isIncomplete() ? ', may be short' : '',
                $queue->isARehearsal() ? ', rehearsed' : '',
                implode(' | ', $items),
                implode(' | ', $unreachable),
                implode(' | ', $runs),
            ));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with every unhealthy item, what could not be reached, and every run with each act on its own', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsQueue()));
    $said = [];

    foreach (everyWayOfAskingTheQueue($answered) as $which => $make) {
        $said[$which] = everythingTheQueueSays($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe(sprintf(
            'healing on after 3 strikes, grace 1800s, classified %d, mismatch ""; items {sonarr/Show.S01E01.1080p/Show stalled (No seed for an hour) 2 strikes added %d grace unsaid next [remove,blocklist,search] | qbittorrent/Unknown.Release/ orphaned (In no curator\'s queue) 1 strikes added unsaid grace %d next []}; unreachable {radarr (It refused the key)}; runs {run at %d: sonarr/Old.Release/Old (Failed three times) [remove=done, blocklist=fell-back instead remove (The curator keeps no blocklist), search=refused (No indexer answered)] | rehearsal at %d: }',
            WHEN_THE_QUEUE_WAS_CLASSIFIED,
            WHEN_THE_QUEUE_WAS_CLASSIFIED - 7_200,
            WHEN_THE_QUEUE_WAS_CLASSIFIED + 600,
            WHEN_THE_QUEUE_WAS_CLASSIFIED - 300,
            WHEN_THE_QUEUE_WAS_CLASSIFIED - 3_600,
        ));
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingTheQueue($answered, $why) as $which => $make) {
            expect(everythingTheQueueSays($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('reads a rehearsed answer, a list that may be short and a client filing outside the curator\'s folder', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'queue', 'data' => ['healing' => 'off', 'strikes_needed' => 0, 'grace_s' => 0, 'incomplete' => true, 'rehearsed' => true, 'mismatch' => 'qbittorrent files sonarr\'s downloads under /downloads/tv']]))]);

    expect(everythingTheQueueSays(new Healers(new PinnedClients())))
        ->toBe('healing off after 0 strikes, grace 0s, classified unsaid, mismatch "qbittorrent files sonarr\'s downloads under /downloads/tv", may be short, rehearsed; items {}; unreachable {}; runs {}');
});

// `queue` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read, rather than drawing a refused act as one that was done', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsQueue($changed)))]);

    expect(everythingTheQueueSays(new Healers(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a switch it has no case for' => [['healing' => 'sometimes']],
    'strikes needed below nothing' => [['strikes_needed' => -1]],
    'a grace below nothing' => [['grace_s' => -1]],
    'no word on whether the list is short' => [['incomplete' => 'no']],
    'no word on whether it was rehearsed' => [['rehearsed' => null]],
    'a moment before the epoch' => [['classified_at' => -1]],
    'a class it has no case for' => [['items' => [['service' => 'sonarr', 'release' => 'A', 'class' => 'sulking', 'why' => 'b', 'strikes' => 1]]]],
    'strikes below nothing' => [['items' => [['service' => 'sonarr', 'release' => 'A', 'class' => 'slow', 'why' => 'b', 'strikes' => -1]]]],
    'an item held by no service' => [['items' => [['service' => ' ', 'release' => 'A', 'class' => 'slow', 'why' => 'b', 'strikes' => 1]]]],
    'an item with no release' => [['items' => [['service' => 'sonarr', 'class' => 'slow', 'why' => 'b', 'strikes' => 1]]]],
    'a next act it has no case for' => [['items' => [['service' => 'sonarr', 'release' => 'A', 'class' => 'slow', 'why' => 'b', 'strikes' => 1, 'next' => ['shrug']]]]],
    'a next act that is not a word' => [['items' => [['service' => 'sonarr', 'release' => 'A', 'class' => 'slow', 'why' => 'b', 'strikes' => 1, 'next' => [7]]]]],
    'an item that is not an object' => [['items' => ['sonarr']]],
    'items that are not a list' => [['items' => 'sonarr']],
    'an unreachable service with no reason' => [['unreachable' => [['service' => 'radarr']]]],
    'a run with no time' => [['runs' => [['rehearsed' => false]]]],
    'an act it has no case for' => [['runs' => [['at' => 1, 'rehearsed' => false, 'items' => [['service' => 'sonarr', 'release' => 'A', 'why' => 'b', 'acts' => [['act' => 'shrug', 'outcome' => 'done']]]]]]]],
    'an outcome it has no case for' => [['runs' => [['at' => 1, 'rehearsed' => false, 'items' => [['service' => 'sonarr', 'release' => 'A', 'why' => 'b', 'acts' => [['act' => 'remove', 'outcome' => 'maybe']]]]]]]],
    'something weaker it has no case for' => [['runs' => [['at' => 1, 'rehearsed' => false, 'items' => [['service' => 'sonarr', 'release' => 'A', 'why' => 'b', 'acts' => [['act' => 'blocklist', 'outcome' => 'fell-back', 'instead' => 'shrug']]]]]]]],
    'an act that is not an object' => [['runs' => [['at' => 1, 'rehearsed' => false, 'items' => [['service' => 'sonarr', 'release' => 'A', 'why' => 'b', 'acts' => ['remove']]]]]]],
    'a run that is not an object' => [['runs' => [1]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('QueueEnvelope', whatAStackSaysOfItsQueue()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
