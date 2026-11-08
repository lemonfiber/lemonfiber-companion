<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\Kind;
use Lemonfiber\Sdk\Generated\PushEndpointsEnvelope;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `push-endpoints` envelope a removal answers with, for the name it says it removed. */
final readonly class PushEndpointRemoved
{
    /**
     * The name the stack says it removed, refusing an answer that names none.
     *
     * @param Envelope<mixed> $envelope the `push-endpoints` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): string
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PushEndpointIsUnreadable::missing(Kind::PushEndpoints, WireField::Data);
        }

        return Required::text($data, WireField::Removed, PushEndpointIsUnreadable::missing(Kind::PushEndpoints, WireField::Removed));
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
        return PushEndpointsEnvelope::in($envelope)->data;
    }
}
