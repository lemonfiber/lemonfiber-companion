<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\AMonitor;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfMonitor;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatNoMonitorCovers;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Kernel\Api\WhenItSays;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheWatcherReads;
use Modules\Operator\Internal\ViewModels\AMonitorAsShown;
use Modules\Operator\Internal\ViewModels\AThingUncoveredAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheWatcherAsShown;

it('draws nothing of the watcher where the session ended or the stack could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(new HowTheWatcherReads()->signedOut())->toEqual(new TheWatcherAsShown(HowTheReadingWent::theSessionEnded(), '', '', AgoAsShown::saysNothing(), [], []))
        ->and(new HowTheWatcherReads()->met($why))->toEqual(new TheWatcherAsShown(HowTheReadingWent::somethingStopped($why), '', '', AgoAsShown::saysNothing(), [], []));
});

it('draws a monitor that covers nothing it names, waits for no heartbeat and was set up as meant with nothing beside it', function (): void {
    $now = Instant::atEpochSeconds(1_790_000_000);
    $since = Instant::atEpochSeconds(1_790_000_000 - 600);
    $sees = WhatTheWatcherSees::answering('uptime-kuma', $now, WhatNoMonitorCovers::these(), AMonitor::named('Ping', KindOfMonitor::Ping, HowAMonitorStands::Up, $since, WhenItSays::unsaid()));

    expect(new HowTheWatcherReads()->this($sees, $now, null)->monitors)->toEqual([new AMonitorAsShown(
        name: 'Ping',
        kindSaid: 'watcher.kind.ping',
        stateSaid: 'watcher.state.up',
        tone: 'fine',
        since: AgoAsShown::since($since, $now),
        switchedOff: false,
        covers: new AThingUncoveredAsShown('', ''),
        pathSaid: '',
        missing: AgoAsShown::saysNothing(),
        neverCame: false,
        wanted: '',
        watches: '',
        why: '',
        stackSaid: '',
        disagree: false,
    )]);
});
