<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SettingsEnvelope;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SettingsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What every reader of the `settings` envelope reads alike: the payload, a list, a value that may be none, and services named in a list.
 */
final readonly class SettingsRows
{
    /**
     * The payload, as it actually arrived.
     *
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    public static function payload(Envelope $envelope): array
    {
        $data = self::data(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SettingsAreUnreadable::missing(WireField::Data->value);
        }

        return $data;
    }

    /**
     * The rows of a list, refused where they are not one.
     *
     * @param  array<mixed> $rows
     * @return list<mixed>
     */
    public static function listed(array $rows, string $where): array
    {
        if (! array_is_list($rows)) {
            throw SettingsAreUnreadable::missing($where);
        }

        return $rows;
    }

    /**
     * A value a row may leave out or send as null, apart from one set to nothing.
     *
     * @param array<mixed> $row
     */
    public static function held(array $row, NamesAWireField $field, string $where): ASettingValue
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return ASettingValue::nothing();
        }

        if (! is_string($row[$field->value])) {
            throw SettingsAreUnreadable::missing(APath::beneath($where, $field->value));
        }

        return ASettingValue::value($row[$field->value]);
    }

    /**
     * Each service a list names, in its order.
     *
     * @param array<mixed> $named
     */
    public static function services(array $named, string $where): Services
    {
        $services = [];

        foreach (self::listed($named, $where) as $position => $service) {
            if (! is_string($service)) {
                throw SettingsAreUnreadable::missing(APath::beneath($where, $position));
            }

            $services[] = ServiceId::called($service);
        }

        return Services::these(...$services);
    }

    /**
     * What the envelope carries, as it actually arrived, which may not be what its type says.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function data(Envelope $envelope): mixed
    {
        return SettingsEnvelope::in($envelope)->data;
    }
}
