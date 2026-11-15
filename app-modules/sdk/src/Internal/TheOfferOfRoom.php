<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function array_map;
use function count;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SpaceEnvelope;
use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhyItemsStayed;
use Modules\Sdk\Api\Fields\SpaceField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SpaceIsUnreadable;
use Modules\Sdk\Api\WhereTheRoomIs;
use Modules\Sdk\Api\WireField;

use function sprintf;

/**
 * Reads the `space` envelope's offer of what costs nothing to give back, and what an answered cleanup came to.
 */
final readonly class TheOfferOfRoom
{
    /**
     * The categories offered back as costing nothing that hold anything, under the offer's name.
     *
     * @param array<mixed> $data the `space` payload
     */
    public static function in(array $data): TheRoomOnOffer
    {
        $offered = [];

        foreach (InTheOrderSent::rows(self::table($data, SpaceField::Reclaimable->value, SpaceField::Reclaimable)) as $position => $row) {
            $where = sprintf(WhereTheRoomIs::AN_ENTRY, SpaceField::Reclaimable->value, $position);

            if (! is_array($row)) {
                throw SpaceIsUnreadable::row(SpaceField::Reclaimable->value, $position);
            }

            $category = self::offered($row, $where);

            if ($category instanceof ACategoryOffered) {
                $offered[] = $category;
            }
        }

        return TheRoomOnOffer::named(
            Required::text($data, WireField::Agreement, SpaceIsUnreadable::missing(WireField::Agreement->value)),
            ...$offered,
        );
    }

    /**
     * What the cleanup the envelope answers came to.
     *
     * @param Envelope<mixed> $envelope the `space` envelope a finished cleanup answered with
     */
    public static function cameBackIn(Envelope $envelope): TheRoomGivenBack
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SpaceIsUnreadable::missing(WireField::Data->value);
        }

        $reclaimed = self::table($data, SpaceField::Reclaimed->value, SpaceField::Reclaimed);
        $left = [];

        foreach (InTheOrderSent::rows(self::table($reclaimed, WireField::Left->under(SpaceField::Reclaimed), WireField::Left)) as $position => $row) {
            $where = sprintf(WhereTheRoomIs::AN_ENTRY, WireField::Left->under(SpaceField::Reclaimed), $position);

            if (! is_array($row)) {
                throw SpaceIsUnreadable::row(WireField::Left->under(SpaceField::Reclaimed), $position);
            }

            $left[] = Required::text($row, WireField::Why, SpaceIsUnreadable::missing(APath::beneath($where, WireField::Why->value)));
        }

        return TheRoomGivenBack::reported(
            self::figure($reclaimed, WireField::Bytes->under(SpaceField::Reclaimed), WireField::Bytes),
            count(self::table($reclaimed, WireField::Gone->under(SpaceField::Reclaimed), WireField::Gone)),
            WhyItemsStayed::because(...$left),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see \Modules\Sdk\Api\Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return SpaceEnvelope::in($envelope)->data;
    }

    /**
     * One line of what could be got back, as a category on offer where it costs nothing and holds anything.
     *
     * @param array<mixed> $row
     */
    private static function offered(array $row, string $where): ?ACategoryOffered
    {
        $said = Required::text($row, SpaceField::Reclaim, SpaceIsUnreadable::missing(APath::beneath($where, SpaceField::Reclaim->value)));
        $costs = WhatGettingItBackCosts::tryFrom($said) ?? throw SpaceIsUnreadable::word(APath::beneath($where, SpaceField::Reclaim->value), $said, ...array_map(static fn(WhatGettingItBackCosts $case): string => $case->value, WhatGettingItBackCosts::cases()));

        if (! $costs->costsNothing()) {
            return null;
        }

        $at = APath::beneath($where, WireField::Category->value);
        $of = Required::text(self::table($row, $at, WireField::Category), WireField::Of, SpaceIsUnreadable::missing(APath::beneath($at, WireField::Of->value)));
        $about = WhatALineIsAbout::tryFrom($of) ?? throw SpaceIsUnreadable::word(APath::beneath($at, WireField::Of->value), $of, ...array_map(static fn(WhatALineIsAbout $case): string => $case->value, WhatALineIsAbout::cases()));
        $tally = APath::beneath($where, SpaceField::Tally->value);
        $counted = self::table($row, $tally, SpaceField::Tally);
        $physical = self::figure($counted, APath::beneath($tally, SpaceField::Physical->value), SpaceField::Physical);
        $files = self::figure($counted, APath::beneath($tally, WireField::Files->value), WireField::Files);

        if ($physical === 0 && $files === 0) {
            return null;
        }

        return ACategoryOffered::holding(
            $about,
            WhatItOccupies::counted(self::figure($counted, APath::beneath($tally, SpaceField::Logical->value), SpaceField::Logical), $physical),
            $files,
        );
    }

    /**
     * A field required to hold an object or a list.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, string $where, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw SpaceIsUnreadable::missing($where);
        }

        return $data[$field->value];
    }

    /**
     * A count that cannot be below zero.
     *
     * @param array<mixed> $data
     */
    private static function figure(array $data, string $where, NamesAWireField $field): int
    {
        return Required::count($data, $field, SpaceIsUnreadable::missing($where));
    }
}
