<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** Where removing a stack from this phone stands, once the operator asked for it. */
enum WhereRemovingTheStackStands
{
    /** The operator is being asked whether to remove the stack. */
    case Asked;

    /** The removal could not begin, and nothing was removed. */
    case Refused;

    /** The stack could not be told to forget this phone's endpoint, and the operator is told it still holds it before the stack is removed. */
    case StillHoldsTheAddress;
}
