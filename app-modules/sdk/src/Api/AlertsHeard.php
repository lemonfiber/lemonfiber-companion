<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Generated\AlertEnvelope;
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Sdk\Api\Fields\AlertField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `alert` events one wake of a stream carried, as the alerts the stack raised.
 *
 * Only what a notification is filed and worded by is read: the kind, which way
 * it went, and the name an onset and its resolution share. What happened and
 * what to do are the stack's to say on its own screens.
 */
final readonly class AlertsHeard
{
    /** Every alert, in the order the stream carried them, refused whole where any cannot be read. */
    public static function in(ServerEvent ...$events): TheAlerts
    {
        $alerts = [];

        foreach ($events as $event) {
            $alerts[] = self::alert(AlertEnvelope::in(Wire::checked(new EnvelopeReader()->read($event->data)))->data);
        }

        return TheAlerts::of(...$alerts);
    }

    private static function alert(mixed $data): AnAlert
    {
        if (! is_array($data)) {
            throw AlertIsUnreadable::missing(WireField::Data->value);
        }

        $moment = Required::text($data, AlertField::Moment, AlertIsUnreadable::missing(AlertField::Moment->value));
        $kind = WhatTheCoreDecided::toSay(Required::text($data, WireField::Kind, AlertIsUnreadable::missing(WireField::Kind->value)));
        $way = WhichWayItWent::tryFrom($moment)
            ?? throw AlertIsUnreadable::word(AlertField::Moment->value, $moment, ...array_map(static fn(WhichWayItWent $way): string => $way->value, WhichWayItWent::cases()));
        $named = trim(Optional::text($data, WireField::Id, AlertIsUnreadable::missing(WireField::Id->value)));

        return $named === '' ? AnAlert::unnamed($kind, $way) : AnAlert::raised($kind, $way, WhatTheCoreDecided::toSay($named));
    }
}
