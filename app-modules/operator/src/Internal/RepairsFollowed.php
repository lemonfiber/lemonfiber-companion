<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** The two jobs the repairs screen follows: asking what the stack would put right, then the yes to one repair. */
enum RepairsFollowed
{
    use TellsTheJobsApart;

    case Asking;
    case TheYes;
}
