<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\InFlightEnvelope;
use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\TheDownloadsComingDown;
use Modules\Sdk\Api\Fields\InFlightField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `in-flight` envelope, as every download still coming down inside what would stop.
 */
final readonly class TheInFlightRead
{
    /** @param Envelope<mixed> $envelope the `in-flight` envelope, as the client returned it */
    public static function in(Envelope $envelope): TheDownloadsComingDown
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw InFlightIsUnreadable::missing(WireField::Data);
        }

        $coming = [];

        foreach (Required::rows($data, InFlightField::Downloads, InFlightIsUnreadable::missing(InFlightField::Downloads)) as $download) {
            if (! is_array($download)) {
                throw InFlightIsUnreadable::missing(InFlightField::Downloads);
            }

            $coming[] = ADownloadItWouldInterrupt::inFlight(
                Required::text($download, WireField::Name, InFlightIsUnreadable::missing(WireField::Name)),
                HowADownloadComes::named(Required::text($download, WireField::Protocol, InFlightIsUnreadable::missing(WireField::Protocol))),
                Required::number($download, WireField::Progress, InFlightIsUnreadable::missing(WireField::Progress)),
            );
        }

        return TheDownloadsComingDown::these(...$coming);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return InFlightEnvelope::in($envelope)->data;
    }
}
