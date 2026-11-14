<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\NotifyMineEnvelope;
use Modules\Kernel\Api\AWayToBeTold;
use Modules\Kernel\Api\WaysToBeTold;
use Modules\Sdk\Api\Fields\NotifyMineField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the ways offered out of the `notify-mine` envelope, in the core's order.
 *
 * `rehearsed` is not read: the app sends no rehearsal of a choice.
 */
final readonly class HowBeingToldRead
{
    /** @param Envelope<mixed> $envelope the `notify-mine` envelope the read or the choice answered with */
    public static function in(Envelope $envelope): WaysToBeTold
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw BeingToldIsUnreadable::missing(WireField::Data->value);
        }

        $ways = [];

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Offered, BeingToldIsUnreadable::missing(WireField::Offered->value))) as $position => $row) {
            $ways[] = self::way($row, APath::beneath(WireField::Offered->value, $position));
        }

        return Required::flag($data, NotifyMineField::Phone, BeingToldIsUnreadable::missing(NotifyMineField::Phone->value))
            ? WaysToBeTold::wakingThisPhone(...$ways)
            : WaysToBeTold::notWakingThisPhone(...$ways);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return NotifyMineEnvelope::in($envelope)->data;
    }

    private static function way(mixed $row, string $where): AWayToBeTold
    {
        if (! is_array($row)) {
            throw BeingToldIsUnreadable::missing($where);
        }

        return AWayToBeTold::offered(
            Required::text($row, WireField::Id, BeingToldIsUnreadable::missing(APath::beneath($where, WireField::Id->value))),
            Required::text($row, WireField::Name, BeingToldIsUnreadable::missing(APath::beneath($where, WireField::Name->value))),
            Required::flag($row, NotifyMineField::Receiving, BeingToldIsUnreadable::missing(APath::beneath($where, NotifyMineField::Receiving->value))),
        );
    }
}
