<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\WatchedEnvelope;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\ThePlace;
use Modules\Sdk\Api\Fields\WatchedField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `watched` envelope, read into the place the core says it kept.
 *
 * The title, how far in, and whether it was the end, as the core answered
 * them; each is required, since an answer missing one is not a place kept.
 */
final readonly class PlacesKept
{
    /**
     * @param Envelope<mixed> $envelope the `watched` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ThePlace
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw PlaceIsUnreadable::missing(WireField::Data);
        }

        $in = HoldingId::called(Required::text($data, WireField::Id, PlaceIsUnreadable::missing(WireField::Id)));
        $howFarIn = HowFarIn::at(Required::number($data, WireField::Position, PlaceIsUnreadable::missing(WireField::Position)));

        return Required::flag($data, WatchedField::Ended, PlaceIsUnreadable::missing(WatchedField::Ended))
            ? ThePlace::atTheEndOf($in, $howFarIn)
            : ThePlace::in($in, $howFarIn);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return WatchedEnvelope::in(Wire::checked($envelope))->data;
    }
}
