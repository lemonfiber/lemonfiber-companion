<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** Where asking for notifications stands, while the reason for them is open. */
enum WhereAskingForNotificationsStands
{
    /** The reason is said, and the phone may be asked. */
    case Explained;

    /** The phone was told no, so asking it again would show nothing. */
    case Declined;
}
