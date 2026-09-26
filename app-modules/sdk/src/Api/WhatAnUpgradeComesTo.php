<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UpgradeEnvelope;
use Modules\Kernel\Api\OneKindUpgraded;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Sdk\Api\Fields\UpgradeField;
use Modules\Sdk\Internal\WhatAQualityAnswerCarries;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `upgrade` envelope into what upgrading the library comes to, kind by kind.
 *
 * Written the way {@see WhatIsChosen} is. Whether it was carried out is the
 * stack's `confirmed`, read rather than remembered from what was asked: the
 * answer says what happened, and a screen told *carried out* on the strength
 * of having asked would say so of an upgrade the stack only described.
 */
final readonly class WhatAnUpgradeComesTo
{
    /**
     * The upgrade, described or carried out.
     *
     * @param Envelope<mixed> $envelope the `upgrade` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheUpgrade
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw QualityIsUnreadable::missing(UpgradeEnvelope::KIND->value, WireField::Data);
        }

        $kinds = self::media($data);

        return self::confirmed($data) ? TheUpgrade::carriedOut(...$kinds) : TheUpgrade::described(...$kinds);
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
        return UpgradeEnvelope::in($envelope)->data;
    }

    /**
     * Whether the operator said yes.
     *
     * @param array<mixed> $data
     */
    private static function confirmed(array $data): bool
    {
        if (! array_key_exists(UpgradeField::Confirmed->value, $data) || ! is_bool($data[UpgradeField::Confirmed->value])) {
            throw QualityIsUnreadable::missing(UpgradeEnvelope::KIND->value, UpgradeField::Confirmed);
        }

        return $data[UpgradeField::Confirmed->value];
    }

    /**
     * Every kind of media the upgrade covers, in the stack's order.
     *
     * @param  array<mixed>             $data
     * @return list<OneKindUpgraded>
     */
    private static function media(array $data): array
    {
        if (! array_key_exists(UpgradeField::Media->value, $data) || ! is_array($data[UpgradeField::Media->value])) {
            throw QualityIsUnreadable::missing(UpgradeEnvelope::KIND->value, UpgradeField::Media);
        }

        $found = [];
        $position = 0;

        foreach ($data[UpgradeField::Media->value] as $row) {
            if (! is_array($row)) {
                throw QualityIsUnreadable::entry(UpgradeEnvelope::KIND->value, UpgradeField::Media, UpgradeField::MediaType, $position);
            }

            $found[] = OneKindUpgraded::reported(
                self::inMedia($row, UpgradeField::MediaType, $position),
                self::inMedia($row, WireField::Preset, $position),
                self::inMedia($row, WireField::SizePerHour, $position),
                WhatAQualityAnswerCarries::asking($row, UpgradeEnvelope::KIND->value),
            );
            $position++;
        }

        return $found;
    }

    /**
     * A required field of one kind of media, as text.
     *
     * @param array<mixed> $row
     */
    private static function inMedia(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw QualityIsUnreadable::entry(UpgradeEnvelope::KIND->value, UpgradeField::Media, $field, $position);
        }

        return $row[$field->value];
    }
}
