<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DeviceKeysEnvelope;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APhoneKey;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\ThePhoneKeys;
use Modules\Kernel\Api\WhereAPhoneKeyLives;
use Modules\Sdk\Api\Fields\DeviceKeysField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `device-keys` envelope into the phone keys a stack lists.
 *
 * A static fold with no state, refusing by position anything the kernel would
 * refuse, with {@see PhoneKeysAreUnreadable}. Only what the requirements ask for
 * is read; the rest is recorded in `WhatThisAppDoesNotRead`, each with its reason.
 */
final readonly class PhoneKeysListed
{
    /**
     * Every phone key the stack lists.
     *
     * @param Envelope<mixed> $envelope the `device-keys` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ThePhoneKeys
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PhoneKeysAreUnreadable::missing(WireField::Data);
        }

        return ThePhoneKeys::of(...self::keys(Required::rows($data, WireField::Keys, PhoneKeysAreUnreadable::missing(WireField::Keys))));
    }

    /**
     * Each phone key the list holds, in the order the stack wrote them.
     *
     * @param array<mixed> $listed
     *
     * @return list<APhoneKey>
     */
    private static function keys(array $listed): array
    {
        if (! array_is_list($listed)) {
            throw PhoneKeysAreUnreadable::missing(WireField::Keys);
        }

        $keys = [];

        foreach ($listed as $position => $row) {
            $keys[] = self::one($row, $position);
        }

        return $keys;
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
        return DeviceKeysEnvelope::in($envelope)->data;
    }

    /** One phone key, refused where any of it is unreadable. */
    private static function one(mixed $row, int $position): APhoneKey
    {
        if (! is_array($row)) {
            throw PhoneKeysAreUnreadable::entry(WireField::Keys, $position);
        }

        $name = APhoneKeysName::of(Required::text($row, WireField::Name, PhoneKeysAreUnreadable::entry(WireField::Name, $position)));
        $backing = Required::text($row, DeviceKeysField::Backing, PhoneKeysAreUnreadable::entry(DeviceKeysField::Backing, $position));
        $lives = WhereAPhoneKeyLives::tryFrom($backing) ?? throw PhoneKeysAreUnreadable::backing($backing, $position);
        $registered = AMomentAsWritten::byTheStack(Required::text($row, DeviceKeysField::Registered, PhoneKeysAreUnreadable::entry(DeviceKeysField::Registered, $position)));
        $used = AMomentAsWritten::byTheStack(Optional::text($row, WireField::Used, PhoneKeysAreUnreadable::entry(WireField::Used, $position)));
        $revoked = Optional::text($row, WireField::Revoked, PhoneKeysAreUnreadable::entry(WireField::Revoked, $position));

        return $revoked === ''
            ? APhoneKey::standing($name, $lives, $registered, $used)
            : APhoneKey::revoked($name, $lives, $registered, $used, AMomentAsWritten::byTheStack($revoked));
    }
}
