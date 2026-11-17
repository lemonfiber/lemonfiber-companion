<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\TunedEnvelope;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\LiveStreamIsUnfit;
use Modules\Kernel\Api\WhatStoppingCameTo;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Sdk\Api\Fields\TunedField;
use Modules\Sdk\Internal\Located;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatEachObstacleSaid;
use Modules\Sdk\Internal\Wire;

/**
 * The `tuned` envelope, read into a channel's live stream, why the core cannot locate one, or when a renewal lapses.
 *
 * Where the core states why nothing is located, that is the answer, in its
 * own words, as a title's is. Otherwise a stream with nothing to renew it by,
 * no door to pin, no lapse or nowhere to play refuses the whole answer.
 */
final readonly class WhatTheTunerOpened
{
    /** @param Envelope<mixed> $envelope the `tuned` envelope a channel opened with */
    public static function in(Envelope $envelope): WhatTuningCameTo
    {
        $data = self::data($envelope);

        if (array_key_exists(WireField::Unlocated->value, $data) && $data[WireField::Unlocated->value] !== null) {
            return WhatTuningCameTo::cannot(WhatEachObstacleSaid::of($data[WireField::Unlocated->value], TunedIsUnreadable::missing(WireField::Unlocated)));
        }

        $refused = TunedIsUnreadable::notAStream();

        try {
            return WhatTuningCameTo::tuned(ALiveStream::opened(
                Required::text($data, WireField::Stream, TunedIsUnreadable::missing(WireField::Stream)),
                Located::live($data, TunedField::Direct, TunedIsUnreadable::missing(TunedField::Direct)),
                Located::live($data, TunedField::Transcoded, TunedIsUnreadable::missing(TunedField::Transcoded)),
                Fingerprint::of(Required::text($data, WireField::Fingerprint, TunedIsUnreadable::missing(WireField::Fingerprint))),
                self::lapses($data),
            ));
        } catch (LiveStreamIsUnfit|FingerprintIsNotAFingerprint) {
            throw $refused;
        }
    }

    /**
     * When a renewed stream's passes now lapse.
     *
     * @param Envelope<mixed> $envelope the `tuned` envelope a renewal answered
     */
    public static function renewed(Envelope $envelope): Instant
    {
        return self::lapses(self::data($envelope));
    }

    /**
     * That the core closed the stream, which it answers with a `tuned` envelope.
     *
     * @param Envelope<mixed> $envelope
     */
    public static function stopped(Envelope $envelope): WhatStoppingCameTo
    {
        self::data($envelope);

        return WhatStoppingCameTo::stopped();
    }

    /**
     * The payload, refused where it is not a table.
     *
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function data(Envelope $envelope): array
    {
        $data = self::payload($envelope);

        return is_array($data) ? $data : throw TunedIsUnreadable::missing(WireField::Data);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return TunedEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<mixed> $data */
    private static function lapses(array $data): Instant
    {
        $refused = TunedIsUnreadable::missing(WireField::Lapses);

        try {
            return Instant::atEpochSeconds(Required::number($data, WireField::Lapses, $refused));
        } catch (InstantIsBeforeTheEpoch) {
            throw $refused;
        }
    }
}
