<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** The next attempt at a failed delivery: when, and which attempt of how many; said by nothing where none waits. */
final readonly class ARetryAsShown
{
    public function __construct(
        public string $said,
        public AgoAsShown $in,
        public int $attempt,
        public int $of,
    ) {}
}
