<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function is_array;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\ReplayEnvelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Sdk\Api\Fields\ReplayField;
use Modules\Sdk\Api\ReplayIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What the `replay` a stream opened after an event says first, read for whether every alert follows.
 *
 * Only a readable one saying so vouches for what follows. Another event, a
 * `replay` that does not read, and one saying `partial` all leave some alerts
 * that may be missing.
 */
final readonly class WhatTheReplaySaid
{
    /** Whether this event is a `replay` saying every alert raised since the id follows it. */
    public static function everyAlertFollows(ServerEvent $event): bool
    {
        if ($event->kind !== ReplayEnvelope::KIND->value) {
            return false;
        }

        try {
            return WhatAReplayHolds::tryFrom(self::alertsIn(ReplayEnvelope::in(Wire::checked(new EnvelopeReader()->read($event->data)))->data)) === WhatAReplayHolds::EveryAlert;
        } catch (ApiVersionMismatch|UnreadableResponse|UnexpectedKind|EnvelopeIsNotRead|ReplayIsUnreadable) {
            return false;
        }
    }

    /** What a replay's payload says of the alerts that follow. */
    private static function alertsIn(mixed $said): string
    {
        if (! is_array($said)) {
            throw ReplayIsUnreadable::missing(WireField::Data);
        }

        return Required::text($said, ReplayField::Alerts, ReplayIsUnreadable::missing(ReplayField::Alerts));
    }
}
