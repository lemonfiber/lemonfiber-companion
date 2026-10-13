<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\EnvelopeReader;
use Lemonfiber\Sdk\Events\ServerEvent;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\AlertEnvelope;
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\CodeIsBlank;
use Modules\Kernel\Api\CodeIsMisshapen;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Sdk\Api\Fields\AlertField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;
use Psr\Log\LoggerInterface;

use function sprintf;
use function trim;

/**
 * The `alert` events one wake of a stream carried, as the alerts the stack raised.
 *
 * Only what a notification is filed and worded by is read: the kind, which way
 * it went, and the name an onset and its resolution share. What happened and
 * what to do are the stack's to say on its own screens.
 *
 * **An alert it cannot read is skipped and noted, and the rest still arrive.**
 * One misshapen alert is no reason to stop hearing a stack, so unlike every
 * other reader of the stream this one refuses one event rather than the wake.
 * The note says why and nothing the stack sent beside it.
 */
final readonly class AlertsHeard
{
    /** The note a skipped alert leaves. */
    private const string SKIPPED = 'alert: skipped one this app cannot read (%s)';

    /** Every alert it can read, in the order the stream carried them, each it cannot noted in `$notes`. */
    public static function in(LoggerInterface $notes, ServerEvent ...$events): TheAlerts
    {
        $alerts = [];

        foreach ($events as $event) {
            try {
                $alerts[] = self::alert(AlertEnvelope::in(Wire::checked(new EnvelopeReader()->read($event->data)))->data);
            } catch (AlertIsUnreadable|CodeIsBlank|CodeIsMisshapen|UnreadableResponse|ApiVersionMismatch|UnexpectedKind|EnvelopeIsNotRead $why) {
                $notes->notice(sprintf(self::SKIPPED, $why::class));
            }
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

        return $named === '' ? AnAlert::unnamed($kind, $way) : AnAlert::raised($kind, $way, AnAlertsName::of($named));
    }
}
