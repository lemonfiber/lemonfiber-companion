<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_values;
use function expect;
use function it;

use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheLastLines;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheScheduleReads;
use Modules\Operator\Internal\ViewModels\ALastRunAsShown;
use Modules\Operator\Internal\ViewModels\AnExitAsShown;
use Modules\Operator\Internal\ViewModels\ARunAndExitAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheScheduleAsShown;

it('draws no service where the session ended or the stack could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(new HowTheScheduleReads()->signedOut())->toEqual(new TheScheduleAsShown(HowTheReadingWent::theSessionEnded(), []))
        ->and(new HowTheScheduleReads()->met($why))->toEqual(new TheScheduleAsShown(HowTheReadingWent::somethingStopped($why), []));
});

it('gives each health its own tone, and a failed run alone its exit and its lines', function (): void {
    $now = Instant::atEpochSeconds(1_790_000_000);
    $run = static fn(?int $exit): ItsLastRun => $exit === null
        ? ItsLastRun::endedWithoutACode(Instant::atEpochSeconds(1_789_999_000), Instant::atEpochSeconds(1_789_999_900), TheLastLines::these('a line'))
        : ItsLastRun::exited(Instant::atEpochSeconds(1_789_999_000), Instant::atEpochSeconds(1_789_999_900), $exit, TheLastLines::these('a line'));
    $shown = new HowTheScheduleReads()->these(TheScheduledServices::these(
        ARunAndExit::notRunYet(ServiceId::called('a'), 'A', running: true, fired: false, skipped: 1),
        ARunAndExit::lastRan(ServiceId::called('b'), 'B', HowItsLastRunWent::Succeeded, $run(0), running: false, fired: true, skipped: 0),
        ARunAndExit::lastRan(ServiceId::called('c'), 'C', HowItsLastRunWent::Failed, $run(3), running: false, fired: true, skipped: 0),
        ARunAndExit::lastRan(ServiceId::called('d'), 'D', HowItsLastRunWent::Failed, $run(null), running: false, fired: true, skipped: 0),
    ), $now);
    $ran = static fn(AnExitAsShown $exit, string ...$log): ALastRunAsShown => new ALastRunAsShown(AgoAsShown::since(Instant::atEpochSeconds(1_789_999_900), $now), 'scheduled.took.minutes', 15, $exit, array_values($log));

    expect($shown->went)->toEqual(HowTheReadingWent::itCameBack())
        ->and($shown->services)->toEqual([
            new ARunAndExitAsShown('A', 'scheduled.health.not-run', 'quiet', running: true, fired: false, skipped: 1, last: ALastRunAsShown::none()),
            new ARunAndExitAsShown('B', 'scheduled.health.succeeded', 'fine', running: false, fired: true, skipped: 0, last: $ran(new AnExitAsShown('', 0))),
            new ARunAndExitAsShown('C', 'scheduled.health.failed', 'trouble', running: false, fired: true, skipped: 0, last: $ran(new AnExitAsShown('scheduled.exit.code', 3), 'a line')),
            new ARunAndExitAsShown('D', 'scheduled.health.failed', 'trouble', running: false, fired: true, skipped: 0, last: $ran(new AnExitAsShown('scheduled.exit.none', 0), 'a line')),
        ]);
});
