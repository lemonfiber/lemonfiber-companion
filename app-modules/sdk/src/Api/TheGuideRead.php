<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\GuideEnvelope;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheGuide;
use Modules\Sdk\Api\Fields\GuideField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `guide` envelope, read into every channel's programmes one member may read.
 *
 * A channel or a programme that cannot be read refuses the whole answer, for
 * {@see TheChannelsListed}'s reason: a guide missing one reads as a gap the
 * core left for the member's limits.
 */
final readonly class TheGuideRead
{
    /** @param Envelope<mixed> $envelope the `guide` envelope, as the client returned it */
    public static function in(Envelope $envelope): TheGuide
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw GuideIsUnreadable::missing(WireField::Data);
        }

        $channels = [];

        foreach (Required::rows($data, WireField::Channels, GuideIsUnreadable::missing(WireField::Channels)) as $at => $channel) {
            $refused = GuideIsUnreadable::channel((int) $at);
            $channels[] = is_array($channel) ? self::channel($channel, $refused) : throw $refused;
        }

        return TheGuide::of(...$channels);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return GuideEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<mixed> $channel */
    private static function channel(array $channel, GuideIsUnreadable $refused): AChannelsGuide
    {
        $airings = [];

        foreach (Required::rows($channel, GuideField::Programmes, $refused) as $airing) {
            $airings[] = is_array($airing) ? self::airing($airing, $refused) : throw $refused;
        }

        return AChannelsGuide::of(
            ChannelId::called(Required::text($channel, WireField::Id, $refused)),
            Required::text($channel, WireField::Name, $refused),
            Optional::text($channel, WireField::Number, $refused),
            Optional::instant($channel, GuideField::GuideUntil, $refused),
            ...$airings,
        );
    }

    /** @param array<mixed> $airing */
    private static function airing(array $airing, GuideIsUnreadable $refused): AnAiring
    {
        return AnAiring::of(
            Required::text($airing, WireField::Title, $refused),
            Instant::atEpochSeconds(Required::number($airing, WireField::Starts, $refused)),
            Instant::atEpochSeconds(Required::number($airing, WireField::Ends, $refused)),
            Optional::text($airing, WireField::Certificate, $refused),
        );
    }
}
