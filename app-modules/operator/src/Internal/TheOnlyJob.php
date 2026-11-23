<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** What a screen following one job at a time follows it for: there is only the one. */
enum TheOnlyJob
{
    case Followed;
}
