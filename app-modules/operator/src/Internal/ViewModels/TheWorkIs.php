<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where work put to a stack is, once the stack could be asked. */
enum TheWorkIs
{
    /** Nothing has been put to the stack from this screen yet. */
    case NotAsked;

    /** The stack is at it. */
    case Running;

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    case Ended;

    /** The stack answered: what it did, or what it refused. */
    case Answered;
}
