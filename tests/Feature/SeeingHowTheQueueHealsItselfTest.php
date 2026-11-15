<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadInTheQueue;
use Modules\Kernel\Api\AHealingAct;
use Modules\Kernel\Api\AHealingRun;
use Modules\Kernel\Api\AnActTaken;
use Modules\Kernel\Api\AnItemActedOn;
use Modules\Kernel\Api\AServiceHealingCouldNotReach;
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
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyItIsWedged;
use Modules\Operator\Internal\Screens\TheQueueHealingItself;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysHowItsQueueHeals;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// How the queue heals itself: each item before it is acted on, and each act of every run on its own.

/** When the screen is drawn, in these tests. */
const WHEN_THE_QUEUE_IS_READ = 1_790_000_000;

/** The machine this screen is about. */
function theHealingStack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** Healing switched on, after three strikes and half an hour's grace, classified a minute ago. */
function aQueueHealing(WhetherHealingActs $healing = WhetherHealingActs::On): HowTheQueueHeals
{
    return HowTheQueueHeals::switched($healing, 3, HowLong::ofSeconds(1_800), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 60)), '');
}

/** The episode sonarr holds. */
function theEpisode(): ADownloadInTheQueue
{
    return ADownloadInTheQueue::heldBy(ServiceId::called('sonarr'), 'Show.S01E01.1080p', 'Show');
}

/** The screen, signed in unless said. */
function theQueueScreen(AStackThatSaysHowItsQueueHeals $asking, bool $signedIn = true): TheQueueHealingItself
{
    $stack = theHealingStack();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheQueueHealingItself(
        $asking,
        $keychain,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ)),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * A line of the queue screen, as the catalogue says it.
 *
 * @param array<string, string> $with
 */
function aQueueLine(string $key, array $with = []): string
{
    return Catalogue::words()->for($key, $with);
}

/**
 * What the screen draws for this queue.
 *
 * @return list<string>
 */
function whatTheQueueScreenSays(HowTheQueueHeals $queue): array
{
    return WhatTheDeviceWouldDraw::by(theQueueScreen(AStackThatSaysHowItsQueueHeals::with($queue)))->said();
}

it('is served at its own path', function (): void {
    expect(data_get(NativeRouter::resolve(str_replace('{stack}', theHealingStack()->id()->stored(), AStacksScreen::Queue->value)), 'class'))->toBe(TheQueueHealingItself::class);
});

it('says whether healing acts, after how many strikes and how long a new download is left alone', function (): void {
    $on = whatTheQueueScreenSays(aQueueHealing());
    $off = whatTheQueueScreenSays(aQueueHealing(WhetherHealingActs::Off));

    expect($on)->toContain(aQueueLine('queue.healing.on'))
        ->and($on)->toContain(trans_choice('queue.strikes_needed', 3))
        ->and($on)->toContain(trans_choice('queue.grace.minutes', 30))
        ->and($on)->toContain(aQueueLine('queue.classified', ['ago' => trans_choice('health.ago.minutes', 1)]))
        ->and($off)->toContain(aQueueLine('queue.healing.off'))
        ->and($off)->not->toContain(aQueueLine('queue.healing.on'));
});

it('shows an item the queue would act on with why it was classified and how many strikes it holds, before the action', function (): void {
    $stalled = AWedgedItem::flagged(theEpisode(), WhyItIsWedged::Stalled, 'No seed for an hour', 2)
        ->added(WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 7_200)))
        ->nextWould(AHealingAct::Remove, AHealingAct::Blocklist, AHealingAct::Search);
    $said = whatTheQueueScreenSays(aQueueHealing()->finding($stalled));

    expect($said)->toContain('Show')
        ->and($said)->toContain(aQueueLine('queue.release', ['release' => 'Show.S01E01.1080p']))
        ->and($said)->toContain(aQueueLine('queue.held_by', ['service' => 'sonarr']))
        ->and($said)->toContain(aQueueLine('queue.class.stalled'))
        ->and($said)->toContain('No seed for an hour')
        ->and($said)->toContain(aQueueLine('queue.strikes', ['strikes' => '2', 'needed' => '3']))
        ->and($said)->toContain(aQueueLine('queue.added', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($said)->toContain(aQueueLine('queue.next'))
        ->and($said)->toContain(aQueueLine('queue.act.remove'))
        ->and($said)->toContain(aQueueLine('queue.act.blocklist'))
        ->and($said)->toContain(aQueueLine('queue.act.search'));
});

it('names a download by its release where no curator holds a title, and says when its grace ends', function (): void {
    $orphan = AWedgedItem::flagged(ADownloadInTheQueue::heldBy(ServiceId::called('qbittorrent'), 'Unknown.Release', ''), WhyItIsWedged::Orphaned, 'In no curator\'s queue', 1)
        ->leftAloneUntil(WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ + 3 * 3_600)));
    $past = AWedgedItem::flagged(theEpisode(), WhyItIsWedged::Slow, 'Too slow', 1)
        ->leftAloneUntil(WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 60)));
    $said = whatTheQueueScreenSays(aQueueHealing()->finding($orphan, $past));

    expect($said)->toContain('Unknown.Release')
        ->and($said)->not->toContain(aQueueLine('queue.release', ['release' => 'Unknown.Release']))
        ->and($said)->toContain(aQueueLine('queue.in_grace', ['in' => trans_choice('health.in.hours', 3)]))
        ->and(array_filter($said, static fn(string $line): bool => str_starts_with($line, aQueueLine('queue.in_grace', ['in' => '']))))->toHaveCount(1)
        ->and(array_filter($said, static fn(string $line): bool => $line === aQueueLine('queue.next_none')))->toHaveCount(2);
});

it('shows removing, blocklisting and searching again as three acts, each with what became of it', function (): void {
    $run = AHealingRun::ranAt(
        Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 300),
        AnItemActedOn::because(
            theEpisode(),
            'Failed three times',
            AnActTaken::took(AHealingAct::Remove, WhatBecameOfAnAct::Done, ''),
            AnActTaken::took(AHealingAct::Blocklist, WhatBecameOfAnAct::FellBack, 'The curator keeps no blocklist')->inPlaceOf(AHealingAct::Remove),
            AnActTaken::took(AHealingAct::Search, WhatBecameOfAnAct::Refused, 'No indexer answered'),
        ),
    );
    $said = whatTheQueueScreenSays(aQueueHealing()->after($run));

    expect($said)->toContain(aQueueLine('queue.ran', ['ago' => trans_choice('health.ago.minutes', 5)]))
        ->and($said)->toContain('Failed three times')
        ->and($said)->toContain(aQueueLine('queue.act_came_to', ['act' => aQueueLine('queue.act.remove'), 'outcome' => aQueueLine('queue.outcome.done')]))
        ->and($said)->toContain(aQueueLine('queue.act_came_to', ['act' => aQueueLine('queue.act.blocklist'), 'outcome' => aQueueLine('queue.outcome.fell-back')]))
        ->and($said)->toContain(aQueueLine('queue.instead', ['act' => aQueueLine('queue.act.remove')]))
        ->and($said)->toContain('The curator keeps no blocklist')
        ->and($said)->toContain(aQueueLine('queue.act_came_to', ['act' => aQueueLine('queue.act.search'), 'outcome' => aQueueLine('queue.outcome.refused')]))
        ->and($said)->toContain('No indexer answered')
        ->and($said)->not->toContain(aQueueLine('queue.rehearsal'));
});

it('labels a rehearsed run as a rehearsal that cleared nothing, never as items cleared', function (): void {
    $rehearsal = AHealingRun::rehearsedAt(
        Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 600),
        AnItemActedOn::because(theEpisode(), 'Stalled three times', AnActTaken::took(AHealingAct::Remove, WhatBecameOfAnAct::Would, '')),
    );
    $empty = AHealingRun::ranAt(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 900));
    $actless = AHealingRun::ranAt(Instant::atEpochSeconds(WHEN_THE_QUEUE_IS_READ - 1_200), AnItemActedOn::because(theEpisode(), 'Struck out'));
    $said = whatTheQueueScreenSays(aQueueHealing()->after($rehearsal, $empty, $actless));

    expect($said)->toContain(aQueueLine('queue.rehearsal'))
        ->and($said)->toContain(aQueueLine('queue.act_came_to', ['act' => aQueueLine('queue.act.remove'), 'outcome' => aQueueLine('queue.outcome.would')]))
        ->and($said)->not->toContain(aQueueLine('queue.act_came_to', ['act' => aQueueLine('queue.act.remove'), 'outcome' => aQueueLine('queue.outcome.done')]))
        ->and($said)->toContain(aQueueLine('queue.run_acted_on_nothing'))
        ->and($said)->toContain(aQueueLine('queue.no_acts'));
});

it('labels a rehearsed answer, a list that may be short and a client filing outside the curator\'s folder', function (): void {
    $queue = HowTheQueueHeals::switched(WhetherHealingActs::On, 3, HowLong::ofSeconds(7_200), WhenItSays::unsaid(), 'qbittorrent files sonarr\'s downloads under /downloads/tv')->mayBeShort()->rehearsed();
    $said = whatTheQueueScreenSays($queue);

    expect($said)->toContain(aQueueLine('queue.rehearsal'))
        ->and($said)->toContain(aQueueLine('queue.incomplete'))
        ->and($said)->toContain(aQueueLine('queue.mismatch'))
        ->and($said)->toContain('qbittorrent files sonarr\'s downloads under /downloads/tv')
        ->and($said)->toContain(trans_choice('queue.grace.hours', 2))
        ->and($said)->toContain(aQueueLine('queue.never_classified'));
});

it('shows what it could not reach as its own answer, never as nothing needing attention', function (): void {
    $unreached = whatTheQueueScreenSays(aQueueHealing()->notReaching(AServiceHealingCouldNotReach::because(ServiceId::called('radarr'), 'It refused the key')));
    $reached = whatTheQueueScreenSays(aQueueHealing());

    expect($unreached)->toContain('radarr')
        ->and($unreached)->toContain('It refused the key')
        ->and($unreached)->not->toContain(aQueueLine('queue.nothing_unreachable'))
        ->and($reached)->toContain(aQueueLine('queue.nothing_unreachable'))
        ->and($reached)->toContain(aQueueLine('queue.nothing_wedged'))
        ->and($reached)->toContain(aQueueLine('queue.no_runs'));
});

it('asks for a session rather than the queue where this device holds none', function (): void {
    $asking = AStackThatSaysHowItsQueueHeals::with(aQueueHealing());
    $screen = theQueueScreen($asking, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($asking->askings())->toBe(0);
});

it('says what stood in the way where the stack could not be asked', function (): void {
    $screen = theQueueScreen(AStackThatSaysHowItsQueueHeals::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aQueueLine(KindOfObstacle::StackDidNotAnswer->said()));
});

it('asks once a frame, and again when asked again', function (): void {
    $asking = AStackThatSaysHowItsQueueHeals::with(aQueueHealing());
    $screen = theQueueScreen($asking);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->answer();

    expect($asking->askings())->toBe(1);

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($asking->askings())->toBe(2);
});

it('is reached from what happened while nobody was looking', function (): void {
    expect(theQueueScreen(AStackThatSaysHowItsQueueHeals::with(aQueueHealing()))->goes()->to(AStacksScreen::Queue))
        ->toBe(sprintf('/stacks/%s/queue', theHealingStack()->id()->stored()));
});
