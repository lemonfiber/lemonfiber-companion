<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Operator\Internal\Presenters\HowAServiceReads;

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
    ));

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
