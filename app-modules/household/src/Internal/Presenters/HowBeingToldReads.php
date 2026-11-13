<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\AWayToBeToldAsShown;
use Modules\Household\Internal\ViewModels\HowTheyAreToldAsShown;
use Modules\Kernel\Api\WaysToBeTold;

/** The ways a member may be told, as the choice the Requests tab draws. */
final readonly class HowBeingToldReads
{
    /** The key for a phone registered to be woken. */
    public const string WOKEN = 'household.told.phone_woken';

    /** The key for a phone that is not. */
    public const string NOT_WOKEN = 'household.told.phone_not_woken';

    public function these(WaysToBeTold $ways): HowTheyAreToldAsShown
    {
        if ($ways->isEmpty()) {
            return HowTheyAreToldAsShown::nothingOffered();
        }

        $shown = [];

        foreach ($ways as $way) {
            $shown[] = new AWayToBeToldAsShown($way->name(), $way->id(), $way->receiving());
        }

        return HowTheyAreToldAsShown::offered($shown, $ways->wakesThisPhone() ? self::WOKEN : self::NOT_WOKEN);
    }
}
