<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** Why what the household is playing is not asked after on this screen. */
enum WhyPlayingIsNotFollowed
{
    /** This device holds no session for the stack. */
    case SignedOut;

    /** The session held is a member's, and only the operator's asks. */
    case AMembersSession;
}
