<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HandoffEnvelope;
use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\ASignedInDevice;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\TheClientsToHandOver;
use Modules\Kernel\Api\TheSignedInDevices;
use Modules\Kernel\Api\TheStepsOnTheirDevice;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToHandThem;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Sdk\Api\Fields\HandoffField;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `handoff` envelope into where handing one person's device over stands.
 *
 * A static fold with no state, for {@see Tellings}' reason. Every list is read
 * whole or refused by position with {@see HandoffIsUnreadable}: steps one short
 * are steps somebody follows to nowhere. The moments are kept as the stack and
 * the media server wrote them, and read against the phone's clock where they
 * are shown.
 */
final readonly class Handoffs
{
    /**
     * Where the hand-off stands.
     *
     * @param Envelope<mixed> $envelope the `handoff` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AHandoff
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HandoffIsUnreadable::missing(WireField::Data);
        }

        return AHandoff::answered(
            SomebodyInTheHousehold::called(self::text($data, WireField::Name)),
            self::stands($data),
            self::optional($data, WireField::Reason),
            self::next($data),
            WhatToHandThem::of(
                self::address($data),
                TheStepsOnTheirDevice::of(...self::steps($data)),
                TheClientsToHandOver::of(...self::clients($data)),
            ),
            AMomentAsWritten::of(self::optional($data, HandoffField::Issued)),
            TheSignedInDevices::of(...self::sessions($data)),
        );
    }

    /**
     * Where it stands, from the closed set the contract names.
     *
     * @param array<array-key, mixed> $data
     */
    private static function stands(array $data): WhereTheHandoffStands
    {
        $said = self::text($data, WireField::State);

        return WhereTheHandoffStands::tryFrom($said) ?? throw HandoffIsUnreadable::unnamed(WireField::State, $said);
    }

    /**
     * What there is to do next, and `Nothing` where the stack sent `null` or nothing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function next(array $data): WhatTheHandoffNeedsNext
    {
        $said = self::optional($data, WireField::Remedy);

        return WhatTheHandoffNeedsNext::tryFrom($said) ?? throw HandoffIsUnreadable::unnamed(WireField::Remedy, $said);
    }

    /**
     * The address the code carries with its caution, or none where the stack sent none.
     *
     * @param array<array-key, mixed> $data
     */
    private static function address(array $data): AnAddressToHand
    {
        $url = self::optional($data, WireField::Address);

        return $url === '' ? AnAddressToHand::none() : AnAddressToHand::at($url, self::optional($data, WireField::Caution));
    }

    /**
     * Every step, in the stack's order.
     *
     * @param  array<array-key, mixed> $data
     * @return list<string>
     */
    private static function steps(array $data): array
    {
        $found = [];

        foreach (self::rows($data, WireField::Steps) as $position => $step) {
            $found[] = is_string($step) ? $step : throw HandoffIsUnreadable::row(WireField::Steps, $position);
        }

        return $found;
    }

    /**
     * Every app, in the stack's order, open-source ones first as it sent them.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<AClientToHandOver>
     */
    private static function clients(array $data): array
    {
        $found = [];

        foreach (self::rows($data, HandoffField::Clients) as $position => $row) {
            $found[] = AClientToHandOver::named(
                self::cell($row, WireField::Device, HandoffField::Clients, $position),
                self::cell($row, WireField::Client, HandoffField::Clients, $position),
                self::flag($row, WireField::OpenSource, $position),
                self::cell($row, WireField::Code, HandoffField::Clients, $position),
                self::flag($row, HandoffField::DeepLink, $position),
            );
        }

        return $found;
    }

    /**
     * Every device signed in now, in the stack's order.
     *
     * @param  array<array-key, mixed> $data
     * @return list<ASignedInDevice>
     */
    private static function sessions(array $data): array
    {
        $found = [];

        foreach (self::rows($data, HandoffField::Sessions) as $position => $row) {
            $found[] = ASignedInDevice::listed(
                self::cell($row, WireField::Device, HandoffField::Sessions, $position),
                self::cell($row, WireField::Client, HandoffField::Sessions, $position),
                AMomentAsWritten::of(self::lastSeen($row, $position)),
            );
        }

        return $found;
    }

    /**
     * A list the answer owes, as it arrived.
     *
     * @param  array<array-key, mixed> $data
     * @return list<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value]) || ! array_is_list($data[$list->value])) {
            throw HandoffIsUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /** A word one entry of a list owes. */
    private static function cell(mixed $row, NamesAWireField $field, NamesAWireField $list, int $position): string
    {
        if (! is_array($row) || ! array_key_exists($field->value, $row) || ! is_string($row[$field->value])) {
            throw HandoffIsUnreadable::row($list, $position);
        }

        return $row[$field->value];
    }

    /** When the media server last heard from one device: empty where it sent `null` or nothing. */
    private static function lastSeen(mixed $row, int $position): string
    {
        if (is_array($row) && (! array_key_exists(HandoffField::LastSeen->value, $row) || $row[HandoffField::LastSeen->value] === null)) {
            return '';
        }

        return self::cell($row, HandoffField::LastSeen, HandoffField::Sessions, $position);
    }

    /** A yes or a no one app owes. */
    private static function flag(mixed $row, NamesAWireField $field, int $position): bool
    {
        if (! is_array($row) || ! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw HandoffIsUnreadable::row(HandoffField::Clients, $position);
        }

        return $row[$field->value];
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
        return HandoffEnvelope::in($envelope)->data;
    }

    /**
     * A word the answer owes.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw HandoffIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A word the contract makes optional: empty where the stack sent `null` or nothing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function optional(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        return self::text($data, $field);
    }
}
