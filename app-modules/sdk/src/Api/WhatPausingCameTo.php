<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PausingEnvelope;
use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\PausingField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * What pausing or resuming every download client came to, read off the `pausing` envelope.
 *
 * The same reading for the rehearsal and the yes, because the stack answers
 * both with the same report: on a rehearsal no client is asked, so nothing is
 * said after, and that is read as unsaid rather than as stopped.
 */
final readonly class WhatPausingCameTo
{
    /**
     * The stack's report, refused whole where any part of it cannot be read.
     *
     * @param Envelope<mixed> $envelope the `pausing` envelope a finished job answered with
     */
    public static function in(Envelope $envelope): WhatPausingComesTo
    {
        $data = self::data($envelope);
        $clients = [];

        foreach (self::rows($data) as $position => $row) {
            $clients[] = self::client(self::entry($row, $position), $position);
        }

        return WhatPausingComesTo::said(
            self::asked($data),
            WhetherItWasRehearsed::said(self::flag($data, WireField::Rehearsed)),
            self::text($data, WireField::Offer->value, WireField::Offer),
            self::optional($data, WireField::Caution->value, WireField::Caution),
            ...$clients,
        );
    }

    /**
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function data(Envelope $envelope): array
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PausingIsUnreadable::missing(WireField::Data->value);
        }

        return $data;
    }

    /**
     * The payload, typed by its kind, handed back as what it is before it is read.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return PausingEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $data */
    private static function asked(array $data): WhatToDoWithTheDownloads
    {
        $said = self::text($data, WireField::Asked->value, WireField::Asked);

        return WhatToDoWithTheDownloads::tryFrom($said)
            ?? throw PausingIsUnreadable::word(WireField::Asked->value, $said, ...array_map(static fn(WhatToDoWithTheDownloads $case): string => $case->value, WhatToDoWithTheDownloads::cases()));
    }

    /**
     * The clients, as they arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Clients->value, $data) || ! is_array($data[WireField::Clients->value])) {
            throw PausingIsUnreadable::missing(WireField::Clients->value);
        }

        return $data[WireField::Clients->value];
    }

    /** @return array<mixed> */
    private static function entry(mixed $row, int|string $position): array
    {
        if (! is_array($row)) {
            throw PausingIsUnreadable::missing(APath::beneath(WireField::Clients->value, $position));
        }

        return $row;
    }

    /** @param array<mixed> $row */
    private static function client(array $row, int|string $position): ADownloadClientAsked
    {
        $where = APath::beneath(WireField::Clients->value, $position);
        $client = self::text($row, APath::beneath($where, WireField::Client->value), WireField::Client);
        $was = self::said($row, APath::beneath($where, WireField::Was->value), WireField::Was);

        if (array_key_exists(PausingField::Unreached->value, $row) && $row[PausingField::Unreached->value] !== null) {
            return ADownloadClientAsked::unreached($client, $was, self::optional($row, APath::beneath($where, PausingField::Unreached->value), PausingField::Unreached));
        }

        return ADownloadClientAsked::reached($client, $was, self::said($row, APath::beneath($where, WireField::Now->value), WireField::Now));
    }


    /**
     * What a client said it was doing, unsaid where the field is absent or null.
     *
     * @param array<mixed> $row
     */
    private static function said(array $row, string $where, NamesAWireField $field): WhatADownloadClientSaid
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return WhatADownloadClientSaid::Unsaid;
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw PausingIsUnreadable::missing($where);
        }

        return match (WhatADownloadClientSaid::tryFrom($said)) {
            WhatADownloadClientSaid::Fetching => WhatADownloadClientSaid::Fetching,
            WhatADownloadClientSaid::Stopped => WhatADownloadClientSaid::Stopped,
            WhatADownloadClientSaid::Unsaid, null => throw PausingIsUnreadable::word($where, $said, WhatADownloadClientSaid::Fetching->value, WhatADownloadClientSaid::Stopped->value),
        };
    }

    /** @param array<mixed> $data */
    private static function flag(array $data, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw PausingIsUnreadable::missing($field->value);
        }

        return $data[$field->value];
    }

    /** @param array<mixed> $data */
    private static function text(array $data, string $where, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw PausingIsUnreadable::missing($where);
        }

        $said = $data[$field->value];

        if (! Required::isText($said)) {
            throw PausingIsUnreadable::missing($where);
        }

        return $said;
    }

    /**
     * Text the contract allows to be absent or null, empty where it is.
     *
     * @param array<mixed> $data
     */
    private static function optional(array $data, string $where, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        if (! is_string($data[$field->value])) {
            throw PausingIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }
}
