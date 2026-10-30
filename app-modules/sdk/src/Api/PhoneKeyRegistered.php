<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DeviceKeyEnvelope;
use Lemonfiber\Sdk\Generated\Kind;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `device-key` envelope into the phone key a stack just registered. */
final readonly class PhoneKeyRegistered
{
    /**
     * The phone key the stack registered, never used yet.
     *
     * @param Envelope<mixed> $envelope the `device-key` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): APhoneKey
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw MintingIsUnreadable::missing(Kind::DeviceKey, WireField::Data);
        }

        $backing = Required::text($data, WireField::Backing, MintingIsUnreadable::missing(Kind::DeviceKey, WireField::Backing));

        return APhoneKey::standing(
            APhoneKeysName::of(Required::text($data, WireField::Name, MintingIsUnreadable::missing(Kind::DeviceKey, WireField::Name))),
            WhereAPhoneKeyLives::tryFrom($backing) ?? throw MintingIsUnreadable::backing($backing),
            AMomentAsWritten::byTheStack(Required::text($data, WireField::Registered, MintingIsUnreadable::missing(Kind::DeviceKey, WireField::Registered))),
            AMomentAsWritten::unwritten(),
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
        return DeviceKeyEnvelope::in($envelope)->data;
    }
}
