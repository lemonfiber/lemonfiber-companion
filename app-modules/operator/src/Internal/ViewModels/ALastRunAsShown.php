<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * A run-and-exit service's last run as the schedule screen draws it: when it ended, how long it took, and for a failed one its exit and last lines.
 */
final readonly class ALastRunAsShown
{
    /** @param list<string> $log */
    public function __construct(
        public AgoAsShown $ended,
        public string $tookSaid,
        public int $tookCount,
        public AnExitAsShown $exit,
        public array $log,
    ) {}

    /** No run to draw. */
    public static function none(): self
    {
        return new self(AgoAsShown::saysNothing(), '', 0, AnExitAsShown::none(), []);
    }
}
