<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ConfigEnvelope;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Sdk\Internal\Wire;

/**
 * The `config` envelope, read as everything the stack is set to.
 *
 * Written the way {@see Households} and {@see Offers} are — static, reading
 * through {@see WireField} so no field name is spelled twice, and refusing
 * rather than salvaging.
 *
 * **This reads the listing and nothing else on the envelope.** The payload also
 * carries a proposed change and what it would come to — `review`, `changed`,
 * `rehearsed` — and none of it is read here. That is a second reading for the
 * screen that changes a setting, and folding it in now would mean a screen that
 * only wanted to look could not be given the listing without also being handed
 * a half-built change.
 *
 * **`value` and `secret` go in together and come out as one thing.** The
 * contract's row is *one setting, as it is safe to show*: a withheld value is
 * already withheld on the other side, and `value` then carries the stack's own
 * note that it is set. Reading the two fields into {@see WhatASettingHolds}
 * here is what stops a boolean travelling any further into the app, where a
 * template would have to remember which way round it reads.
 */
final readonly class Dials
{
    /**
     * Everything this stack said it is set to, in the order it said it.
     *
     * @param Envelope<mixed> $envelope the `config` envelope
     */
    public static function in(Envelope $envelope): Settings
    {
        // No guard on `data` itself: the generated envelope types it, and a
        // check the analyser can already prove is one that reads as dead to
        // everything except the person adding it. What is not proven is the
        // shape underneath, which is what `listing()` refuses.
        $data = ConfigEnvelope::in(Wire::checked($envelope))->data;

        return Settings::of(...self::each(self::listing($data)));
    }

    /**
     * The `settings` list, refused unless it is one.
     *
     * An absent `settings` is refused rather than read as an empty listing.
     * They draw identically — a screen saying nothing is set — and the two are
     * opposite: one is a stack with nothing set, the other is an answer this
     * app could not read. A stack that has nothing set says so with an empty
     * list, which this accepts.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private static function listing(array $data): array
    {
        if (! array_key_exists(WireField::Settings->value, $data)) {
            throw SettingIsUnreadable::missing(WireField::Settings);
        }

        $listed = $data[WireField::Settings->value];

        if (! is_array($listed)) {
            throw SettingIsUnreadable::notAList();
        }

        return $listed;
    }

    /**
     * Every row of the listing, in order.
     *
     * @param array<mixed> $listed
     * @return list<Setting>
     */
    private static function each(array $listed): array
    {
        $set = [];
        $position = 0;

        foreach ($listed as $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::row($position);
            }

            $set[] = self::one($row);
            $position++;
        }

        return $set;
    }

    /**
     * One row: its name, and what it holds.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row): Setting
    {
        return Setting::called(
            self::text($row, WireField::Key),
            self::holds($row),
        );
    }

    /**
     * Which of the two things this row's value is.
     *
     * The flag is read here and goes no further. Everything after this point
     * has a value it may print or a note it may print, and no way to confuse
     * one for the other.
     *
     * @param array<mixed> $row
     */
    private static function holds(array $row): WhatASettingHolds
    {
        $said = self::text($row, WireField::Value);

        if (! array_key_exists(WireField::Secret->value, $row)) {
            throw SettingIsUnreadable::missing(WireField::Secret);
        }

        $withheld = $row[WireField::Secret->value];

        if (! is_bool($withheld)) {
            // Refused rather than read for truthiness. A `"false"` arriving as
            // a string is true to PHP, and the reading that goes wrong is the
            // one that prints a withheld note as a value.
            throw SettingIsUnreadable::missing(WireField::Secret);
        }

        return $withheld
            ? WhatASettingHolds::withheld($said)
            : WhatASettingHolds::shown($said);
    }

    /**
     * One field of a row, as text.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $field): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw SettingIsUnreadable::missing($field);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw SettingIsUnreadable::missing($field);
        }

        return $said;
    }
}
