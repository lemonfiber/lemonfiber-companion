<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\KeyClaimEnvelope;
use Lemonfiber\Sdk\Generated\Kind;
use Modules\Kernel\Api\AKeyClaim;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Sdk\Api\Fields\KeyClaimField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `key-claim` envelope into the link that collects a key, and when it lapses. */
final readonly class KeyClaimed
{
    /**
     * The key minted as a claim.
     *
     * @param Envelope<mixed> $envelope the `key-claim` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AKeyClaim
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw MintingIsUnreadable::missing(Kind::KeyClaim, WireField::Data);
        }

        return AKeyClaim::of(
            AKeysName::of(Required::text($data, WireField::Name, MintingIsUnreadable::missing(Kind::KeyClaim, WireField::Name))),
            Required::text($data, WireField::Link, MintingIsUnreadable::missing(Kind::KeyClaim, WireField::Link)),
            AMomentAsWritten::byTheStack(Required::text($data, KeyClaimField::Lapses, MintingIsUnreadable::missing(Kind::KeyClaim, KeyClaimField::Lapses))),
        );
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
        return KeyClaimEnvelope::in($envelope)->data;
    }
}
