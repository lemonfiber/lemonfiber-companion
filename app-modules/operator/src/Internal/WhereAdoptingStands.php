<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** Where adopting the operator's own edits stands, once a rehearsal of it was asked for. */
enum WhereAdoptingStands
{
    /** The run being followed rehearses adopting. */
    case Rehearsing;

    /** The report on the screen is a rehearsal of adopting, which a yes may be given to. */
    case Rehearsed;
}
