<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One run-and-exit service as the schedule screen draws it.
 */
final readonly class ARunAndExitAsShown
{
    public function __construct(
        public string $id,
        public string $name,
        public string $healthSaid,
        public string $tone,
        public bool $running,
        public bool $fired,
        public int $skipped,
        public ALastRunAsShown $last,
    ) {}
}
