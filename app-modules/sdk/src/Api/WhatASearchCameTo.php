<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AskableEnvelope;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Sdk\Api\Fields\AskableField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Wire;

/**
 * Reads one page of a member's search out of the `askable` envelope.
 *
 * What the member may ask for is not read here: the core says it in its own
 * sentence when an ask is rehearsed, and on Requests.
 */
final readonly class WhatASearchCameTo
{
    /**
     * The page the envelope holds.
     *
     * @param Envelope<mixed> $envelope the `askable` envelope a search answered with
     */
    public static function in(Envelope $envelope): WhatASearchFound
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SearchIsUnreadable::missing(WireField::Data->value);
        }

        $titles = [];

        foreach (self::rows($data) as $position => $row) {
            $titles[] = self::title($row, $position);
        }

        $approval = self::text($data, WireField::Approval->value, WireField::Approval);

        if (! array_key_exists(WireField::Next->value, $data) || $data[WireField::Next->value] === null) {
            return WhatASearchFound::lastly($approval, ...$titles);
        }

        $next = $data[WireField::Next->value];

        return is_string($next)
            ? WhatASearchFound::before(WhereTheNextPageBegins::at($next), $approval, ...$titles)
            : throw SearchIsUnreadable::missing(WireField::Next->value);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return AskableEnvelope::in($envelope)->data;
    }

    /**
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(AskableField::Titles->value, $data) || ! is_array($data[AskableField::Titles->value])) {
            throw SearchIsUnreadable::missing(AskableField::Titles->value);
        }

        return $data[AskableField::Titles->value];
    }

    private static function title(mixed $row, int|string $position): AFoundTitle
    {
        $where = APath::beneath(AskableField::Titles->value, $position);

        if (! is_array($row)) {
            throw SearchIsUnreadable::missing($where);
        }

        return AFoundTitle::listed(
            self::text($row, APath::beneath($where, WireField::Id->value), WireField::Id),
            self::kind($row, $where),
            self::text($row, APath::beneath($where, WireField::Title->value), WireField::Title),
            self::year($row, $where),
            self::stands($row, $where),
        );
    }

    /** @param array<mixed> $row */
    private static function kind(array $row, string $where): KindOfTitle
    {
        $said = self::text($row, APath::beneath($where, WireField::Kind->value), WireField::Kind);

        return KindOfTitle::tryFrom($said)
            ?? throw SearchIsUnreadable::word(WireField::Kind->value, $said, ...array_map(static fn(KindOfTitle $case): string => $case->value, KindOfTitle::cases()));
    }

    /** @param array<mixed> $row */
    private static function stands(array $row, string $where): WhereAFoundTitleStands
    {
        $said = self::text($row, APath::beneath($where, AskableField::Stands->value), AskableField::Stands);

        return WhereAFoundTitleStands::tryFrom($said)
            ?? throw SearchIsUnreadable::word(AskableField::Stands->value, $said, ...array_map(static fn(WhereAFoundTitleStands $case): string => $case->value, WhereAFoundTitleStands::cases()));
    }

    /** @param array<mixed> $row */
    private static function year(array $row, string $where): WhenItCameOut
    {
        if (! array_key_exists(WireField::Year->value, $row) || $row[WireField::Year->value] === null) {
            return WhenItCameOut::unstated();
        }

        $year = $row[WireField::Year->value];

        return is_int($year) ? WhenItCameOut::in($year) : throw SearchIsUnreadable::missing(APath::beneath($where, WireField::Year->value));
    }

    /** @param array<mixed> $from */
    private static function text(array $from, string $where, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $from) || ! is_string($from[$field->value])) {
            throw SearchIsUnreadable::missing($where);
        }

        return $from[$field->value];
    }
}
