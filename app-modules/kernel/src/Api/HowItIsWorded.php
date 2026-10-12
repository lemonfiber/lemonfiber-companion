<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which words a notification is shown in: a condition starting, one resolving, or a stack with more to tell than is shown.
 */
enum HowItIsWorded: string
{
    case Onset = 'onset';

    case Resolved = 'resolved';

    case MoreToTell = 'more';

    /** The words for an alert that went this way. */
    public static function forAnAlert(WhichWayItWent $way): self
    {
        return match ($way) {
            WhichWayItWent::Onset => self::Onset,
            WhichWayItWent::Resolved => self::Resolved,
        };
    }
}
