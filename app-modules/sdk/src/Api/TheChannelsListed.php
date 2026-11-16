<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ChannelsEnvelope;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\TheChannels;
use Modules\Sdk\Api\Fields\ChannelsField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `channels` envelope, read into the channels one member may watch.
 *
 * A channel that cannot be read refuses the whole answer, for
 * {@see WhereTheyLeftOff}'s reason: a list missing one reads as a channel the
 * member may not watch.
 */
final readonly class TheChannelsListed
{
    /** @param Envelope<mixed> $envelope the `channels` envelope, as the client returned it */
    public static function in(Envelope $envelope): TheChannels
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw ChannelsAreUnreadable::missing(WireField::Data);
        }

        $channels = [];

        foreach (Required::rows($data, ChannelsField::Channels, ChannelsAreUnreadable::missing(ChannelsField::Channels)) as $at => $channel) {
            $channels[] = is_array($channel) ? self::channel($channel, ChannelsAreUnreadable::channel((int) $at)) : throw ChannelsAreUnreadable::channel((int) $at);
        }

        return TheChannels::these(...$channels);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return ChannelsEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<mixed> $channel */
    private static function channel(array $channel, ChannelsAreUnreadable $refused): AChannel
    {
        $id = ChannelId::called(Required::text($channel, WireField::Id, $refused));
        $name = Required::text($channel, WireField::Name, $refused);
        $number = Optional::text($channel, WireField::Number, $refused);
        if (! array_key_exists(WireField::Now->value, $channel) || $channel[WireField::Now->value] === null) {
            return AChannel::withNothingSaid($id, $name, $number);
        }

        $now = Required::rows($channel, WireField::Now, $refused);

        return AChannel::showing($id, $name, $number, AProgramme::of(
            Required::text($now, WireField::Title, $refused),
            Optional::instant($now, ChannelsField::Ends, $refused),
            Optional::text($now, WireField::Certificate, $refused),
        ));
    }
}
