<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Sdk\Api\OriginIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * Who put something there, read off the tagged table the contract sends.
 *
 * Its own reader because three envelopes carry the same table — a setting's
 * value, a finding's check, a service reaching somewhere — and a rule written
 * three times is three chances for one copy to learn to default. Each reader
 * hands over what arrived under `origin` and wraps a refusal in its own, which
 * is the part that knows *which* row it was.
 */
final readonly class Attributions
{
    /**
     * The origin a row carries, on the arm its word names.
     *
     * The word decides, rather than which field happens to be present: a table
     * saying `bundled` and carrying a `named` is bundled, and a name read off
     * the wrong arm would be an attribution the stack never made.
     *
     * @param array<mixed> $row the row the origin is a field of
     */
    public static function of(array $row): WhoPutItThere
    {
        if (! array_key_exists(WireField::Origin->value, $row)) {
            throw OriginIsUnreadable::missing(WireField::Origin);
        }

        $attributed = $row[WireField::Origin->value];

        if (! is_array($attributed)) {
            throw OriginIsUnreadable::missing(WireField::Origin);
        }

        return self::attributed($attributed);
    }

    /**
     * One origin table, on the arm its word names.
     *
     * Its own method because an origin can hold another: what an override
     * replaced carries where that value came from, in the same shape.
     *
     * @param array<mixed> $attributed
     */
    private static function attributed(array $attributed): WhoPutItThere
    {
        $word = self::text($attributed, WireField::Origin);

        // Converted at the boundary and once, which is what every adapter here
        // does with a closed set.
        $who = WhoSetIt::tryFrom($word) ?? throw OriginIsUnreadable::nobodyHere($word);

        // A name or a reason that is there and blank is refused by the kernel,
        // and turned into this reader's refusal here, so an envelope's own
        // refusal covers it without each adapter learning a kernel type — the
        // one that did not would let a stack's blank plugin name end a screen.
        try {
            return match ($who) {
                WhoSetIt::Bundled => WhoPutItThere::bundled(),
                WhoSetIt::Operator => WhoPutItThere::operator(),
                WhoSetIt::Plugin => WhoPutItThere::plugin(self::text($attributed, WireField::Named)),
                WhoSetIt::Unknown => WhoPutItThere::unknown(self::text($attributed, WireField::Why)),
                WhoSetIt::Overridden => WhoPutItThere::overridden(self::text($attributed, WireField::Named), self::replaced($attributed)),
                WhoSetIt::Orphaned => WhoPutItThere::orphaned(self::text($attributed, WireField::Named)),
            };
        } catch (AnOriginIsUnnamed $why) {
            throw OriginIsUnreadable::namingNobody($why);
        }
    }

    /**
     * What an override replaced: a value, nothing set, or a withheld credential, with where it came from.
     *
     * Withheld wins over a value the stack sent beside it, since a credential
     * is never shown whatever else arrived.
     *
     * @param array<mixed> $attributed
     */
    private static function replaced(array $attributed): WhatItReplaced
    {
        if (! array_key_exists(WireField::Replaced->value, $attributed) || ! is_array($attributed[WireField::Replaced->value])) {
            throw OriginIsUnreadable::missing(WireField::Replaced);
        }

        $replaced = $attributed[WireField::Replaced->value];

        if (! array_key_exists(WireField::From->value, $replaced) || ! is_array($replaced[WireField::From->value])) {
            throw OriginIsUnreadable::missing(WireField::From);
        }

        $from = self::attributed($replaced[WireField::From->value]);

        if (! array_key_exists(WireField::Withheld->value, $replaced) || ! is_bool($replaced[WireField::Withheld->value])) {
            throw OriginIsUnreadable::missing(WireField::Withheld);
        }

        if ($replaced[WireField::Withheld->value]) {
            return WhatItReplaced::withheld($from);
        }

        if (! array_key_exists(WireField::Value->value, $replaced) || $replaced[WireField::Value->value] === null) {
            return WhatItReplaced::nothingSet($from);
        }

        return WhatItReplaced::held(self::text($replaced, WireField::Value), $from);
    }

    /**
     * One field of the table, as text.
     *
     * @param array<mixed> $attributed
     */
    private static function text(array $attributed, WireField $field): string
    {
        if (! array_key_exists($field->value, $attributed)) {
            throw OriginIsUnreadable::missing($field);
        }

        $said = $attributed[$field->value];

        if (! is_string($said)) {
            throw OriginIsUnreadable::missing($field);
        }

        return $said;
    }
}
