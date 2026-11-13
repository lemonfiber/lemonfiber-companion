<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AskableKindsEnvelope;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\UnitOfTitle;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Sdk\Api\Fields\AskableKindsField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * Reads what a member can ask for out of the `askable-kinds` envelope, in the core's order.
 *
 * `member` is not read: the session asked is the member's own, so the core
 * answers for them and nobody else.
 */
final readonly class WhatCanBeAskedForCameTo
{
    /**
     * Every kind listed, with its units.
     *
     * @param Envelope<mixed> $envelope the `askable-kinds` envelope the read answered with
     */
    public static function in(Envelope $envelope): WhatCanBeAskedFor
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SearchIsUnreadable::missing(WireField::Data->value);
        }

        $listed = WhatCanBeAskedFor::listing();

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Kinds, SearchIsUnreadable::missing(WireField::Kinds->value))) as $position => $row) {
            $where = APath::beneath(WireField::Kinds->value, $position);

            if (! is_array($row)) {
                throw SearchIsUnreadable::missing($where);
            }

            $listed = $listed->and(self::kind($row, $where), ...self::units($row, $where));
        }

        return $listed;
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return AskableKindsEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $row */
    private static function kind(array $row, string $where): KindOfTitle
    {
        $said = Required::text($row, WireField::Kind, SearchIsUnreadable::missing(APath::beneath($where, WireField::Kind->value)));

        return KindOfTitle::tryFrom($said)
            ?? throw SearchIsUnreadable::word(WireField::Kind->value, $said, ...array_map(static fn(KindOfTitle $case): string => $case->value, KindOfTitle::cases()));
    }

    /**
     * @param  array<mixed>      $row
     * @return list<UnitOfTitle>
     */
    private static function units(array $row, string $where): array
    {
        $units = [];
        $beneath = APath::beneath($where, AskableKindsField::Units->value);

        foreach (InTheOrderSent::rows(Required::rows($row, AskableKindsField::Units, SearchIsUnreadable::missing($beneath))) as $said) {
            $units[] = (is_string($said) ? UnitOfTitle::tryFrom($said) : null)
                ?? throw SearchIsUnreadable::word(AskableKindsField::Units->value, is_string($said) ? $said : '', ...array_map(static fn(UnitOfTitle $case): string => $case->value, UnitOfTitle::cases()));
        }

        return $units;
    }
}
