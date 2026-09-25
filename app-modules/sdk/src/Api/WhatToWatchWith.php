<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ClientsEnvelope;
use Modules\Kernel\Api\ADeviceToWatchOn;
use Modules\Kernel\Api\APossibleCause;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\SomethingThatGoesWrong;
use Modules\Kernel\Api\TheDevices;
use Modules\Kernel\Api\TheTroubles;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Kernel\Api\WhyPlaybackMayStruggle;
use Modules\Sdk\Api\Fields\ClientsField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `clients` envelope into which app to watch on, device by device.
 *
 * Written the way {@see WhatLeaves} is: a static fold with no state, refusing
 * by position anything the kernel would refuse, with {@see ClientsIsUnreadable}.
 * Optional sentences arrive as `null` or absent and are read as empty.
 */
final readonly class WhatToWatchWith
{
    /**
     * The advice in full.
     *
     * @param Envelope<mixed> $envelope the `clients` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatToWatchOn
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ClientsIsUnreadable::missing(WireField::Data);
        }

        return WhatToWatchOn::advised(
            TheDevices::of(...self::devices($data)),
            self::sentence($data, ClientsField::OnlyAtHome),
            self::sentence($data, ClientsField::NothingIsInstalled),
            self::straining($data),
            TheTroubles::of(...self::troubles($data)),
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
        return ClientsEnvelope::in($envelope)->data;
    }

    /**
     * Every device, in the stack's order.
     *
     * @param  array<mixed>           $data
     * @return list<ADeviceToWatchOn>
     */
    private static function devices(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, ClientsField::Devices) as $row) {
            if (! is_array($row)) {
                throw ClientsIsUnreadable::row(ClientsField::Devices, $position);
            }

            $found[] = ADeviceToWatchOn::rated(
                self::text($row, ClientsField::Devices, ClientsField::Device, $position),
                self::text($row, ClientsField::Devices, ClientsField::Client, $position),
                self::support($row, $position),
                self::optional($row, ClientsField::Devices, WireField::Caution, $position),
                self::optional($row, ClientsField::Devices, WireField::Instead, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * How well one device is served.
     *
     * @param array<mixed> $row
     */
    private static function support(array $row, int $position): HowWellADeviceIsServed
    {
        $said = self::text($row, ClientsField::Devices, ClientsField::Support, $position);

        return HowWellADeviceIsServed::tryFrom($said) ?? throw ClientsIsUnreadable::support($said, $position);
    }

    /**
     * Why playback may struggle here, or nothing where the stack sent `null` or nothing.
     *
     * @param array<mixed> $data
     */
    private static function straining(array $data): WhyPlaybackMayStruggle
    {
        if (! array_key_exists(ClientsField::Straining->value, $data) || $data[ClientsField::Straining->value] === null) {
            return WhyPlaybackMayStruggle::nothing();
        }

        $straining = $data[ClientsField::Straining->value];

        if (! is_array($straining)) {
            throw ClientsIsUnreadable::missing(ClientsField::Straining);
        }

        return WhyPlaybackMayStruggle::said(
            self::strained($straining, WireField::Preset),
            self::strained($straining, WireField::Caution),
            self::strained($straining, WireField::Instead),
        );
    }

    /**
     * A required field of what strains playback, as text.
     *
     * @param array<mixed> $straining
     */
    private static function strained(array $straining, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $straining) || ! is_string($straining[$field->value]) || trim($straining[$field->value]) === '') {
            throw ClientsIsUnreadable::strained($field);
        }

        return $straining[$field->value];
    }

    /**
     * Every symptom and what is likely behind it, in the stack's order.
     *
     * @param  array<mixed>                 $data
     * @return list<SomethingThatGoesWrong>
     */
    private static function troubles(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, ClientsField::Trouble) as $row) {
            if (! is_array($row)) {
                throw ClientsIsUnreadable::row(ClientsField::Trouble, $position);
            }

            $found[] = SomethingThatGoesWrong::said(
                self::text($row, ClientsField::Trouble, ClientsField::Symptom, $position),
                ...self::causes($row, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What is likely behind one symptom, most likely first.
     *
     * @param  array<mixed>         $trouble
     * @return list<APossibleCause>
     */
    private static function causes(array $trouble, int $position): array
    {
        if (! array_key_exists(ClientsField::Causes->value, $trouble) || ! is_array($trouble[ClientsField::Causes->value])) {
            throw ClientsIsUnreadable::said(ClientsField::Trouble, ClientsField::Causes, $position);
        }

        $found = [];

        foreach ($trouble[ClientsField::Causes->value] as $cause) {
            if (! is_array($cause)) {
                throw ClientsIsUnreadable::said(ClientsField::Trouble, ClientsField::Causes, $position);
            }

            $found[] = APossibleCause::said(
                self::text($cause, ClientsField::Causes, WireField::Because, $position),
                self::text($cause, ClientsField::Causes, ClientsField::Tell, $position),
                self::text($cause, ClientsField::Causes, ClientsField::Fix, $position),
            );
        }

        return $found;
    }

    /**
     * One list's rows, as they arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw ClientsIsUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A required sentence of the envelope itself.
     *
     * @param array<mixed> $data
     */
    private static function sentence(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw ClientsIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A sentence the contract makes optional: empty where absent or `null`, refused where blank or not text.
     *
     * @param array<mixed> $row
     */
    private static function optional(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return '';
        }

        return self::text($row, $list, $field, $position);
    }

    /**
     * A required field of one row, as text.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $row)) {
            throw ClientsIsUnreadable::said($list, $field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw ClientsIsUnreadable::said($list, $field, $position);
        }

        return $said;
    }
}
