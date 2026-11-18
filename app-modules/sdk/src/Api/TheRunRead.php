<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\RanEnvelope;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Sdk\Internal\ARunRead;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `ran` envelope, as what running a run-and-exit service at once came to: its run, or the stack's reason nothing ran.
 */
final readonly class TheRunRead
{
    /**
     * What the run came to.
     *
     * @param Envelope<mixed> $envelope the `ran` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatRunningItNowCameTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ScheduleIsUnreadable::ran(WireField::Data);
        }

        if (array_key_exists(WireField::Ran->value, $data) && $data[WireField::Ran->value] !== null) {
            return WhatRunningItNowCameTo::ran(ARunRead::in(
                Required::rows($data, WireField::Ran, ScheduleIsUnreadable::ran(WireField::Ran)),
                RanEnvelope::KIND,
            ));
        }

        $held = Optional::text($data, WireField::Held, ScheduleIsUnreadable::ran(WireField::Held));

        return $held === '' ? throw ScheduleIsUnreadable::ran(WireField::Held) : WhatRunningItNowCameTo::held($held);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return RanEnvelope::in($envelope)->data;
    }
}
