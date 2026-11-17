<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the schedule screen draws: how the reading went and every run-and-exit service.
 */
final readonly class TheScheduleAsShown
{
    /** @param list<ARunAndExitAsShown> $services */
    public function __construct(public HowTheReadingWent $went, public array $services) {}
}
