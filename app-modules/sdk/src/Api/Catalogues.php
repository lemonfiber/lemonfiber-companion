<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\CatalogueEnvelope;
use Modules\Kernel\Api\AServiceDropped;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCatalogue;
use Modules\Kernel\Api\WhatAServiceIsFor;
use Modules\Kernel\Api\WhatTheServicesAreFor;
use Modules\Kernel\Api\WhatWasDropped;
use Modules\Sdk\Api\Fields\CatalogueField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `catalogue` envelope, as what each service is for and what became of any the stack dropped.
 *
 * The sibling of {@see Origins}, written the same way: a static fold with no
 * state, reading through {@see WireField}, refusing rather than salvaging.
 * Both lists keep the stack's order.
 *
 * **What took a dropped service's place may be absent**, and absent and `null`
 * are the same answer: nothing did. Anything else there is read, so a blank
 * is refused rather than taken as silence.
 */
final readonly class Catalogues
{
    /**
     * What each service a stack declares is for, and what it dropped.
     *
     * @param Envelope<mixed> $envelope the `catalogue` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheCatalogue
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw CatalogueIsUnreadable::missing(WireField::Data);
        }

        return TheCatalogue::of(
            WhatTheServicesAreFor::these(...self::services($data)),
            WhatWasDropped::these(...self::dropped($data)),
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
        return CatalogueEnvelope::in($envelope)->data;
    }

    /**
     * Every service, refusing any row this app cannot show.
     *
     * @param  array<mixed>            $data
     * @return list<WhatAServiceIsFor>
     */
    private static function services(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, WireField::Services) as $row) {
            if (! is_array($row)) {
                throw CatalogueIsUnreadable::entry(WireField::Services, WireField::Id, $position);
            }

            $found[] = WhatAServiceIsFor::declared(
                ServiceId::called(self::text($row, WireField::Services, WireField::Id, $position)),
                self::text($row, WireField::Services, WireField::Name, $position),
                self::text($row, WireField::Services, WireField::Describes, $position),
                self::text($row, WireField::Services, CatalogueField::WithoutIt, $position),
                self::matters($row, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * How much one service's absence matters, as a word this app reads.
     *
     * @param array<mixed> $row
     */
    private static function matters(array $row, int $position): HowMuchItMatters
    {
        $said = self::text($row, WireField::Services, WireField::Criticality, $position);

        return HowMuchItMatters::tryFrom($said) ?? throw CatalogueIsUnreadable::matters($said, $position);
    }

    /**
     * Every service the stack dropped, with what took its place where the row says.
     *
     * @param  array<mixed>          $data
     * @return list<AServiceDropped>
     */
    private static function dropped(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, CatalogueField::Removed) as $row) {
            if (! is_array($row)) {
                throw CatalogueIsUnreadable::entry(CatalogueField::Removed, WireField::Id, $position);
            }

            $found[] = self::one($row, $position);
            $position++;
        }

        return $found;
    }

    /**
     * One dropped service, replaced or not.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row, int $position): AServiceDropped
    {
        $service = ServiceId::called(self::text($row, CatalogueField::Removed, WireField::Id, $position));
        $removedIn = self::text($row, CatalogueField::Removed, CatalogueField::RemovedIn, $position);
        $reason = self::text($row, CatalogueField::Removed, WireField::Reason, $position);

        if (! array_key_exists(CatalogueField::ReplacedBy->value, $row) || $row[CatalogueField::ReplacedBy->value] === null) {
            return AServiceDropped::went($service, $removedIn, $reason);
        }

        return AServiceDropped::replaced($service, $removedIn, $reason, self::text($row, CatalogueField::Removed, CatalogueField::ReplacedBy, $position));
    }

    /**
     * The rows of one list, as they arrived.
     *
     * Returned with their keys, for {@see Records::rows()}'s reason.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw CatalogueIsUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw CatalogueIsUnreadable::entry($list, $field, $position);
        }

        return $row[$field->value];
    }
}
