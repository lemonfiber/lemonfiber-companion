<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use Lemonfiber\Native\WhyNothingWasKept;
use Modules\Kernel\Api\WhySessionCannotBeKept;

/** The device's reason for keeping nothing, in the words the kernel reads it in. */
final readonly class WhatARefusalToKeepMeans
{
    public static function of(WhyNothingWasKept $why): WhySessionCannotBeKept
    {
        return match ($why) {
            WhyNothingWasKept::NoStoreOnThisDevice => WhySessionCannotBeKept::DeviceHasNoSecureStorage,
            WhyNothingWasKept::StoreWouldNotOpen => WhySessionCannotBeKept::StoreWouldNotOpen,
        };
    }
}
