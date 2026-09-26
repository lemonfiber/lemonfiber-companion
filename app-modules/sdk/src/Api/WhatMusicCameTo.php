<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\MusicEnvelope;
use Modules\Kernel\Api\AFormatChoiceMade;
use Modules\Sdk\Api\Fields\MusicField;
use Modules\Sdk\Internal\WhatAQualityAnswerCarries;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `music` envelope into what choosing a format for music did.
 *
 * The format, what became of the choice and what asking the music service
 * came to are shapes the `quality` and `upgrade` envelopes carry too, read by
 * {@see WhatAQualityAnswerCarries}. So this reads where each sits and nothing
 * else.
 */
final readonly class WhatMusicCameTo
{
    /**
     * What choosing the format did.
     *
     * @param Envelope<mixed> $envelope the `music` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AFormatChoiceMade
    {
        $data = self::payload(Wire::checked($envelope));
        $kind = MusicEnvelope::KIND->value;

        if (! is_array($data)) {
            throw QualityIsUnreadable::missing($kind, WireField::Data);
        }

        if (! array_key_exists(MusicField::Choice->value, $data) || ! is_array($data[MusicField::Choice->value])) {
            throw QualityIsUnreadable::missing($kind, MusicField::Choice);
        }

        return AFormatChoiceMade::reported(
            WhatAQualityAnswerCarries::format($data[MusicField::Choice->value], $kind, MusicField::Choice),
            WhatAQualityAnswerCarries::became($data, $kind),
            WhatAQualityAnswerCarries::asking($data, $kind),
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
        return MusicEnvelope::in($envelope)->data;
    }
}
