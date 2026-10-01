<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;

/**
 * Where this module keeps its settings between launches, sealed.
 *
 * One value for the whole phone rather than one row per setting: a store may
 * be handed nothing it could read, and a setting's name is something it could.
 */
interface SettingsKept extends ForgetsEverythingKept
{
    /** Keep these settings, in this shape, set at this moment, in place of any before. */
    public function keep(SealedPayload $payload, Shape $shape, Instant $setAt): Noted;

    /** The settings kept, or that there are none this build can read. */
    public function kept(): KeptSettings;
}
