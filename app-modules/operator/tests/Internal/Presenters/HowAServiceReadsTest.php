<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Operator\Internal\Presenters\HowAServiceReads;
use Tests\Support\WhatAMachineRuns;

/**
 * Every state a service can be in has the glyph an operator reads it by.
 *
 * Written out case by case rather than sampled, so a state added to the
 * enum without a glyph fails here by name as well as at the match.
 */
it('draws each state a service can be in with its own tone', function (HowAServiceRuns $runs, Tone $tone): void {
    $row = new HowAServiceReads()->in(Daemon::called(
        'Sonarr',
        ServiceId::called('sonarr'),
        $runs,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    ), Daemons::none(WhatAMachineRuns::whatTheVerbsCost()));

    expect($row->tone)->toBe($tone->value);
})->with([
    'running' => [HowAServiceRuns::Running, Tone::Fine],
    'healthy' => [HowAServiceRuns::Healthy, Tone::Fine],
    'run by the host' => [HowAServiceRuns::HostManaged, Tone::Fine],
    'starting' => [HowAServiceRuns::Starting, Tone::Working],
    'stopped' => [HowAServiceRuns::Stopped, Tone::Attention],
    'absent' => [HowAServiceRuns::Absent, Tone::Attention],
    'failed' => [HowAServiceRuns::Failed, Tone::Trouble],
    'crash-looping' => [HowAServiceRuns::CrashLooping, Tone::Trouble],
    'unhealthy' => [HowAServiceRuns::Unhealthy, Tone::Trouble],
]);

it('says what leans on a service by the name the listing gives it, and by its identifier where the listing has none', function (): void {
    $gluetun = Daemon::called(
        'Gluetun',
        ServiceId::called('gluetun'),
        HowAServiceRuns::Failed,
        HowMuchItMatters::Critical,
        WhatLeansOnIt::these(ServiceId::called('qbittorrent'), ServiceId::called('unlisted')),
    );
    $qbittorrent = Daemon::called(
        'qBittorrent',
        ServiceId::called('qbittorrent'),
        HowAServiceRuns::Running,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    );
    $listing = Daemons::of(HowTheStackIsRunning::Degraded, Forms::none(), WhatAMachineRuns::whatTheVerbsCost(), $gluetun, $qbittorrent);

    expect(new HowAServiceReads()->in($gluetun, $listing)->leaning)->toBe(['qBittorrent', 'unlisted']);
});

it('says whether a service stopped with an error, and carries its exit code to its logs', function (int $code, string $said): void {
    $row = new HowAServiceReads()->in(Daemon::thatExited(
        'Gluetun',
        ServiceId::called('gluetun'),
        HowAServiceRuns::Failed,
        HowMuchItMatters::Critical,
        WhatLeansOnIt::nothing(),
        $code,
    ), Daemons::none(WhatAMachineRuns::whatTheVerbsCost()));

    expect($row->stoppedSaid)->toBe($said)
        ->and($row->carriedToTheLogs())->toBe(['exited' => (string) $code]);
})->with([
    'with an error' => [1, 'health.it_stopped_with_an_error'],
    'without one' => [0, 'health.it_stopped_cleanly'],
]);

it('says nothing about stopping, and carries nothing, for a service with no exit code', function (): void {
    $row = new HowAServiceReads()->in(Daemon::called(
        'Gluetun',
        ServiceId::called('gluetun'),
        HowAServiceRuns::Running,
        HowMuchItMatters::Critical,
        WhatLeansOnIt::nothing(),
    ), Daemons::none(WhatAMachineRuns::whatTheVerbsCost()));

    expect($row->stoppedSaid)->toBe('')
        ->and($row->carriedToTheLogs())->toBe([]);
});
