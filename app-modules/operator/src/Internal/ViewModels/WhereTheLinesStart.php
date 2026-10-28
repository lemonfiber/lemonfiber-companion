<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a window of a service's lines starts, against the first line in it that declared an error. */
enum WhereTheLinesStart
{
    /** Above it, so the lines before it may be left out. */
    case AboveTheFirstError;

    /** At it, with the lines before it left out. */
    case AtTheFirstError;
}
