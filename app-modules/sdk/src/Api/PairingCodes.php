<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PairingEnvelope;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Sdk\Api\Fields\PairingField;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `pairing` envelope into the pairing code the stack made.
 *
 * A static fold with no state, for {@see Tellings}' reason. It reads the line,
 * the fingerprint the material carries and the compare code folded from it,
 * the moment it stops being good, the address with its caution, and what
 * replacing the certificate would cost. The same moment written as a date and
 * a time is not read: the phone says it on its own clock. The stack's
 * identifier rides inside the line and is the other phone's to read from it.
 * A fingerprint that is not one is refused by {@see Fingerprint::of()} itself.
 */
final readonly class PairingCodes
{
    /**
     * The code the stack made.
     *
     * @param Envelope<mixed> $envelope the `pairing` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): APairingCode
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PairingIsUnreadable::missing(WireField::Data);
        }

        $material = self::material($data);

        return APairingCode::made(
            APairingLine::asWritten(self::text($data, WireField::Written)),
            Fingerprint::of(self::text($material, WireField::Fingerprint)),
            self::text($data, PairingField::Compare),
            Instant::atEpochSeconds(self::expires($material)),
            self::text($material, WireField::Address),
            self::caution($data),
            self::text($data, PairingField::Replacing),
        );
    }

    /**
     * The material the line was written from, which the address, the fingerprint and the expiry are read out of.
     *
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function material(array $data): array
    {
        if (! array_key_exists(PairingField::Material->value, $data) || ! is_array($data[PairingField::Material->value])) {
            throw PairingIsUnreadable::missing(PairingField::Material);
        }

        return $data[PairingField::Material->value];
    }

    /**
     * When it stops being good, in seconds since the Unix epoch.
     *
     * @param array<array-key, mixed> $material
     */
    private static function expires(array $material): int
    {
        if (! array_key_exists(PairingField::Expires->value, $material) || ! is_int($material[PairingField::Expires->value])) {
            throw PairingIsUnreadable::missing(PairingField::Expires);
        }

        return $material[PairingField::Expires->value];
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
        return PairingEnvelope::in($envelope)->data;
    }

    /**
     * A word the answer owes.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw PairingIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * What is worth knowing about the address, or empty where the stack sent `null` or nothing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function caution(array $data): string
    {
        if (! array_key_exists(WireField::Caution->value, $data) || $data[WireField::Caution->value] === null) {
            return '';
        }

        return self::text($data, WireField::Caution);
    }
}
