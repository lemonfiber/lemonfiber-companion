<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why the stack would not make, or work out, a choice of what fills a capability.
 *
 * One case per refusal the stack names for a choice, so each reaches the
 * operator in words of its own and none is read from the stack's sentence.
 * Every case left nothing changed.
 */
enum WhyTheFillWasTurnedDown
{
    /** The stack has no service by that name. */
    case NoSuchService;

    /** The service does not provide the capability. */
    case CannotFill;

    /** The service already fills it, so nothing needed to change. */
    case AlreadyFills;

    /** Nothing in the stack asks for the capability. */
    case NothingAsks;

    /** The stack has no settings file to record the choice in. */
    case NowhereToKeepIt;

    /** The reading the yes named has moved since it was shown. */
    case Moved;

    /** The reason given cannot be recorded with the choice. */
    case ReasonCannotBeKept;
}
