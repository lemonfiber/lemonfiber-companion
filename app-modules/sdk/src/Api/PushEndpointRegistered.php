<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\Kind;
use Lemonfiber\Sdk\Generated\PushEndpointEnvelope;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `push-endpoint` envelope into the fingerprint a stack names the endpoint it just registered under this phone's name by. */
final readonly class PushEndpointRegistered
{
    /**
     * The fingerprint the stack keeps the endpoint by, refused where it names none, names no fingerprint, or names another address than `$sent`.
     *
     * @param Envelope<mixed> $envelope the `push-endpoint` envelope, as the client returned it
     */
    public static function in(Envelope $envelope, AnEndpoint $sent): AWakeFingerprint
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PushEndpointIsUnreadable::missing(Kind::PushEndpoint, WireField::Data);
        }

        Required::text($data, WireField::Name, PushEndpointIsUnreadable::missing(Kind::PushEndpoint, WireField::Name));

        $unreadable = PushEndpointIsUnreadable::missing(Kind::PushEndpoint, WireField::Fingerprint);

        try {
            $held = AWakeFingerprint::of(Required::text($data, WireField::Fingerprint, $unreadable));
        } catch (AWakeIsUnfit) {
            throw $unreadable;
        }

        return $held->is($sent->fingerprint()) ? $held : throw PushEndpointIsUnreadable::anotherAddress();
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
