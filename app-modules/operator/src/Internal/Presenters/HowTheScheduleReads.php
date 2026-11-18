<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheLastLines;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Operator\Internal\ViewModels\ALastRunAsShown;
use Modules\Operator\Internal\ViewModels\AnExitAsShown;
use Modules\Operator\Internal\ViewModels\ARunAndExitAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheScheduleAsShown;
use Modules\Operator\Internal\ViewModels\WhatRanNowAsShown;

use function sprintf;

/**
 * Turns the stack's run-and-exit services into what the schedule screen draws.
 */
final readonly class HowTheScheduleReads
{
    /** The key for how long a run took, by its unit. */
    private const string TOOK = 'scheduled.took.%s';

    /** The key for a failed run's exit code. */
    private const string EXITED = 'scheduled.exit.code';

    /** The key for a failed run that ended with no exit code. */
    private const string NO_CODE = 'scheduled.exit.none';

    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheScheduleAsShown
    {
        return new TheScheduleAsShown(HowTheReadingWent::theSessionEnded(), []);
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheScheduleAsShown
    {
        return new TheScheduleAsShown(HowTheReadingWent::somethingStopped($why), []);
    }

    /** The stack said how each of its run-and-exit services last ran. */
    public function these(TheScheduledServices $services, Instant $now): TheScheduleAsShown
    {
        $shown = [];

        foreach ($services as $service) {
            $shown[] = $this->service($service, $now);
        }

        return new TheScheduleAsShown(HowTheReadingWent::itCameBack(), $shown);
    }

    /** What running one at once came to: how its run ended, or the stack's reason nothing ran. */
    public function ranNow(WhatRunningItNowCameTo $came, string $name, Instant $now): WhatRanNowAsShown
    {
        return $came->either(
            ran: function (ItsLastRun $run) use ($name, $now): WhatRanNowAsShown {
                $health = $run->succeeded() ? HowItsLastRunWent::Succeeded : HowItsLastRunWent::Failed;

                return new WhatRanNowAsShown($name, $health->saidOnTheScreen(), $this->tone($health), $this->ran($run, ! $run->succeeded(), $now), '');
            },
            held: static fn(string $why): WhatRanNowAsShown => new WhatRanNowAsShown($name, '', Tone::Attention->value, ALastRunAsShown::none(), $why),
        );
    }

    private function service(ARunAndExit $service, Instant $now): ARunAndExitAsShown
    {
        $failed = $service->health() === HowItsLastRunWent::Failed;

        return new ARunAndExitAsShown(
            id: $service->id()->named(),
            name: $service->name(),
            healthSaid: $service->health()->saidOnTheScreen(),
            tone: $this->tone($service->health()),
            running: $service->isRunning(),
            fired: $service->isFired(),
            skipped: $service->skipped(),
            last: $service->last(
                ran: fn(ItsLastRun $run): ALastRunAsShown => $this->ran($run, $failed, $now),
                never: static fn(): ALastRunAsShown => ALastRunAsShown::none(),
            ),
        );
    }

    private function ran(ItsLastRun $run, bool $failed, Instant $now): ALastRunAsShown
    {
        $took = HowLong::ofSeconds($run->ended()->epochSeconds() - $run->started()->epochSeconds());

        return new ALastRunAsShown(
            ended: AgoAsShown::since($run->ended(), $now),
            tookSaid: sprintf(self::TOOK, $took->unit()->value),
            tookCount: $took->howMany(),
            exit: $failed ? $run->exit(
                said: static fn(int $code): AnExitAsShown => new AnExitAsShown(self::EXITED, $code),
                unsaid: static fn(): AnExitAsShown => new AnExitAsShown(self::NO_CODE, 0),
            ) : AnExitAsShown::none(),
            log: $failed ? $this->lines($run->log()) : [],
        );
    }

    /** @return list<string> */
    private function lines(TheLastLines $log): array
    {
        $lines = [];

        foreach ($log as $line) {
            $lines[] = $line;
        }

        return $lines;
    }

    private function tone(HowItsLastRunWent $health): string
    {
        $tone = match ($health) {
            HowItsLastRunWent::NotRun => Tone::Quiet,
            HowItsLastRunWent::Succeeded => Tone::Fine,
            HowItsLastRunWent::Failed => Tone::Trouble,
        };

        return $tone->value;
    }
}
