<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_last;

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;

/** Folds what a job said into what the screen following it draws: its newest line, and every line where they are open. */
final readonly class HowWhatAJobSaidReads
{
    public function of(WhatAJobSaid $said, bool $showingAll): WhatAJobSaidAsShown
    {
        return new WhatAJobSaidAsShown(
            array_last($said->lines),
            $showingAll ? $said->lines : [],
            $showingAll,
            HowManyLines::ON_A_PHONE,
            $said->letGo,
        );
    }
}
