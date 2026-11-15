<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AHeartbeat;
use Modules\Kernel\Api\AMonitor;
use Modules\Kernel\Api\AMonitorNotSetUp;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfMonitor;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAMonitorCovers;
use Modules\Kernel\Api\WhatKindOfThingIsCovered;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatNoMonitorCovers;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WhichWayItLooks;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TheOutsideWatcher;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhatItsWatcherSees;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the outside watcher sees, beside what the stack says of the same services.

/** When the screen is drawn, in these tests. */
const WHEN_THE_WATCHER_IS_READ = 1_790_000_000;

/** The machine this screen is about. */
function theWatchedStack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A monitor of the kind and state given, saying so for ten minutes. */
function aMonitorSaying(string $name, KindOfMonitor $kind, HowAMonitorStands $stands): AMonitor
{
    return AMonitor::named($name, $kind, $stands, Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ - 600), WhenItSays::unsaid());
}

/** A watcher holding these monitors, asked a minute ago, leaving these uncovered. */
function aWatcherHolding(WhatNoMonitorCovers $uncovered, AMonitor ...$monitors): WhatTheWatcherSees
{
    return WhatTheWatcherSees::answering('uptime-kuma', Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ - 60), $uncovered, ...$monitors);
}

/** The stack running sonarr as it says. */
function aStackRunningSonarr(HowAServiceRuns $runs): Daemons
{
    return Daemons::of(HowTheStackIsRunning::Partial, WhatAMachineRuns::whatTheVerbsCost(), Daemon::called('Sonarr', ServiceId::called('sonarr'), $runs, HowMuchItMatters::Core, WhatLeansOnIt::nothing()));
}

/** The screen, signed in unless said. */
function theWatcherScreen(AStackThatSaysWhatItsWatcherSees $asking, ?AStackThatSupervises $supervising = null, bool $signedIn = true): TheOutsideWatcher
{
    $stack = theWatchedStack();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TheOutsideWatcher(
        $asking,
        $supervising ?? AStackThatSupervises::with(aStackRunningSonarr(HowAServiceRuns::Running)),
        $keychain,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ)),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * A line of the watcher screen, as the catalogue says it.
 *
 * @param array<string, string> $with
 */
function aWatcherLine(string $key, array $with = []): string
{
    return Catalogue::words()->for($key, $with);
}

it('is served at its own path', function (): void {
    expect(data_get(NativeRouter::resolve(str_replace('{stack}', theWatchedStack()->id()->stored(), AStacksScreen::Watcher->value)), 'class'))->toBe(TheOutsideWatcher::class);
});

it('draws what the watcher says and what the stack says side by side, each labelled, and marks where they disagree without a verdict', function (): void {
    $sonarr = aMonitorSaying('Sonarr', KindOfMonitor::Http, HowAMonitorStands::Down)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr'))->reachedBy(WhichWayItLooks::Internal);
    $screen = theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $sonarr)));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect($said)->toContain(aWatcherLine('watcher.said_by_the_watcher', ['state' => aWatcherLine('watcher.state.down'), 'ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->toContain(aWatcherLine('watcher.said_by_the_stack', ['state' => aWatcherLine(HowAServiceRuns::Running->saidOnTheScreen())]))
        ->and($said)->toContain(aWatcherLine('watcher.they_disagree'))
        ->and($said)->toContain(aWatcherLine('watcher.covers_this', ['kind' => aWatcherLine('watcher.covers.service'), 'name' => 'sonarr']))
        ->and($said)->toContain(aWatcherLine('watcher.path.internal'));
});

it('marks nothing where the watcher and the stack agree', function (): void {
    $sonarr = aMonitorSaying('Sonarr', KindOfMonitor::Http, HowAMonitorStands::Up)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr'));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $sonarr)), AStackThatSupervises::with(aStackRunningSonarr(HowAServiceRuns::Healthy))))->said();

    expect($said)->not->toContain(aWatcherLine('watcher.they_disagree'))
        ->and($said)->toContain(aWatcherLine('watcher.said_by_the_stack', ['state' => aWatcherLine(HowAServiceRuns::Healthy->saidOnTheScreen())]));
});

it('draws the stack\'s reading only beside a monitor covering that very service', function (): void {
    $radarr = aMonitorSaying('Radarr', KindOfMonitor::Http, HowAMonitorStands::Down)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'radarr'));
    $job = aMonitorSaying('Sonarr job', KindOfMonitor::Heartbeat, HowAMonitorStands::Down)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Job, 'sonarr'));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $radarr, $job))))->said();

    expect($said)->not->toContain(aWatcherLine('watcher.said_by_the_stack', ['state' => aWatcherLine(HowAServiceRuns::Running->saidOnTheScreen())]))
        ->and($said)->not->toContain(aWatcherLine('watcher.they_disagree'));
});

it('tells a monitor that is not reporting apart from a service that is down', function (): void {
    $quiet = aMonitorSaying('Sonarr', KindOfMonitor::Http, HowAMonitorStands::NotReporting)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr'));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $quiet)), AStackThatSupervises::with(aStackRunningSonarr(HowAServiceRuns::Failed))))->said();

    expect($said)->toContain(aWatcherLine('watcher.said_by_the_watcher', ['state' => aWatcherLine('watcher.state.not-reporting'), 'ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->not->toContain(aWatcherLine('watcher.state.down'))
        ->and($said)->not->toContain(aWatcherLine('watcher.they_disagree'));
});

it('says how long a heartbeat has been missing and what it covers, or that it never arrived', function (): void {
    $late = aMonitorSaying('Backups', KindOfMonitor::Heartbeat, HowAMonitorStands::Down)
        ->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Job, 'backup'))
        ->waitingFor(AHeartbeat::due(HowLong::ofSeconds(3_600), HowLong::ofSeconds(300), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ - 3_900 - 7_200))));
    $never = aMonitorSaying('Nightly', KindOfMonitor::Heartbeat, HowAMonitorStands::Pending)->waitingFor(AHeartbeat::due(HowLong::ofSeconds(60), HowLong::ofSeconds(0), WhenItSays::unsaid()));
    $due = aMonitorSaying('Hourly', KindOfMonitor::Heartbeat, HowAMonitorStands::Up)->waitingFor(AHeartbeat::due(HowLong::ofSeconds(3_600), HowLong::ofSeconds(0), WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ - 60))));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $late, $never, $due))))->said();

    expect($said)->toContain(aWatcherLine('watcher.heartbeat_missing', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($said)->toContain(aWatcherLine('watcher.covers_this', ['kind' => aWatcherLine('watcher.covers.job'), 'name' => 'backup']))
        ->and($said)->toContain(aWatcherLine('watcher.heartbeat_never'))
        ->and(array_filter($said, static fn(string $line): bool => str_starts_with($line, aWatcherLine('watcher.heartbeat_missing', ['ago' => '']))))->toHaveCount(1);
});

it('shows what a monitor could not be set up to watch in place of its result', function (): void {
    $front = aMonitorSaying('Front door', KindOfMonitor::Keyword, HowAMonitorStands::Up)->switchedOff()->notSetUp(AMonitorNotSetUp::said('the sign-in page', 'the home page', 'The sign-in page answers only over the tunnel'));
    $paused = aMonitorSaying('Radarr', KindOfMonitor::Http, HowAMonitorStands::Paused);
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $front, $paused))))->said();

    expect($said)->toContain(aWatcherLine('watcher.not_set_up', ['wanted' => 'the sign-in page', 'watches' => 'the home page']))
        ->and($said)->toContain(aWatcherLine('watcher.said_by_the_watcher', ['state' => aWatcherLine('watcher.state.paused'), 'ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->toContain('The sign-in page answers only over the tunnel')
        ->and($said)->toContain(aWatcherLine('watcher.switched_off'));
});

it('lists what no monitor covers, or says everything is covered', function (): void {
    $uncovered = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::EntryPoint, 'jellyfin.home.arpa'))))))->said();
    $covered = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these()))))->said();

    expect($uncovered)->toContain('jellyfin.home.arpa')
        ->and($covered)->toContain(aWatcherLine('watcher.nothing_uncovered'))
        ->and($covered)->toContain(aWatcherLine('watcher.no_monitors'));
});

it('says a watcher that does not answer is blind, in the stack\'s words', function (): void {
    $blind = WhatTheWatcherSees::blind('', 'Nothing fills the watcher capability', Instant::atEpochSeconds(WHEN_THE_WATCHER_IS_READ), WhatNoMonitorCovers::these(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr')));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with($blind)))->said();

    expect($said)->toContain(aWatcherLine('watcher.blind'))
        ->and($said)->toContain('Nothing fills the watcher capability')
        ->and($said)->toContain('sonarr');
});

it('draws the watcher alone where the stack\'s own reading could not be had', function (): void {
    $sonarr = aMonitorSaying('Sonarr', KindOfMonitor::Http, HowAMonitorStands::Down)->covering(WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, 'sonarr'));
    $screen = theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these(), $sonarr)), AStackThatSupervises::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect($said)->toContain(aWatcherLine('watcher.said_by_the_watcher', ['state' => aWatcherLine('watcher.state.down'), 'ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->not->toContain(aWatcherLine('watcher.they_disagree'))
        ->and($screen->servicesAreBeingRead())->toBeFalse();
});

it('asks for a session rather than the watcher where this device holds none', function (): void {
    $asking = AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these()));
    $screen = theWatcherScreen($asking, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($asking->askings())->toBe(0);
});

it('says what stood in the way where the stack could not be asked', function (): void {
    $screen = theWatcherScreen(AStackThatSaysWhatItsWatcherSees::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->servicesAreBeingRead())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aWatcherLine(KindOfObstacle::StackDidNotAnswer->said()));
});

it('reads the stack\'s own services on the frame after the watcher, and again after asking again where they could not be had', function (): void {
    $screen = theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these())), AStackThatSupervises::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->servicesAreBeingRead())->toBeTrue();

    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->servicesAreBeingRead())->toBeFalse();

    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($screen->servicesAreBeingRead())->toBeTrue();
});

it('reads both again when asked again', function (): void {
    $asking = AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these()));
    $screen = theWatcherScreen($asking);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    expect($asking->askings())->toBe(2);
});

it('is reached from what happened while nobody was looking', function (): void {
    expect(theWatcherScreen(AStackThatSaysWhatItsWatcherSees::with(aWatcherHolding(WhatNoMonitorCovers::these())))->goes()->to(AStacksScreen::Watcher))
        ->toBe(sprintf('/stacks/%s/watcher', theWatchedStack()->id()->stored()));
});
