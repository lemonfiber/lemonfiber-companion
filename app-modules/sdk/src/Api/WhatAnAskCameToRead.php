<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AskedEnvelope;
use Modules\Kernel\Api\WhatAnAskBecame;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Internal\Wire;

/**
 * Reads what asking for a title came to out of the `asked` envelope.
 *
 * `said` is the core's sentence for all of it. What it leaves of each kind is
 * on the wire as parts too, and is not read: the sentence says it, and a
 * second wording made here could disagree with the core's.
 */
final readonly class WhatAnAskCameToRead
{
    /**
     * What the envelope says the ask came to.
     *
     * @param Envelope<mixed> $envelope the `asked` envelope the action answered with
     */
    public static function in(Envelope $envelope): WhatAnAskCameTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SearchIsUnreadable::missing(WireField::Data->value);
        }

        if (! array_key_exists(WireField::Rehearsed->value, $data) || ! is_bool($data[WireField::Rehearsed->value])) {
            throw SearchIsUnreadable::missing(WireField::Rehearsed->value);
        }

        return WhatAnAskCameTo::answered(self::became($data), WhetherItWasRehearsed::said($data[WireField::Rehearsed->value]), self::text($data, WireField::Said));
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return AskedEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $data */
    private static function became(array $data): WhatAnAskBecame
    {
        $said = self::text($data, WireField::Outcome);

        return WhatAnAskBecame::tryFrom($said)
            ?? throw SearchIsUnreadable::word(WireField::Outcome->value, $said, ...array_map(static fn(WhatAnAskBecame $case): string => $case->value, WhatAnAskBecame::cases()));
    }

    /** @param array<mixed> $data */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw SearchIsUnreadable::missing($field->value);
        }

        return $data[$field->value];
    }
}
