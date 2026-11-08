<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\Kind;
use Lemonfiber\Sdk\Generated\PushEndpointEnvelope;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Sdk\Api\Fields\PushEndpointField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `push-endpoint` envelope into the endpoint a stack just registered under this phone's name. */
final readonly class PushEndpointRegistered
{
    /**
     * The endpoint as the stack keeps it, refused where it names none or no endpoint.
     *
     * @param Envelope<mixed> $envelope the `push-endpoint` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AnEndpoint
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PushEndpointIsUnreadable::missing(Kind::PushEndpoint, WireField::Data);
        }

        Required::text($data, WireField::Name, PushEndpointIsUnreadable::missing(Kind::PushEndpoint, WireField::Name));

        return AnEndpoint::of(Required::text($data, PushEndpointField::Endpoint, PushEndpointIsUnreadable::missing(Kind::PushEndpoint, PushEndpointField::Endpoint)));
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
        return PushEndpointEnvelope::in($envelope)->data;
    }
}
