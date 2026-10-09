<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PartWayEnvelope;
use Modules\Kernel\Api\APartWay;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\PartWays;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Sdk\Api\Fields\PartWayField;
use Modules\Sdk\Internal\Located;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `part-way` envelope, read into what a member was part-way through.
 *
 * {@see Titles}' line, held for each item: a part the core does not state is
 * unstated here, and an item stated as something else refuses the whole
 * answer, because a row missing one reads as a member who never started it.
 */
final readonly class WhereTheyLeftOff
{
    /**
     * @param Envelope<mixed> $envelope the `part-way` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): PartWays
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw PartWayIsUnreadable::missing(WireField::Data);
        }

        $items = [];

        foreach (Required::rows($data, PartWayField::PartWay, PartWayIsUnreadable::missing(PartWayField::PartWay)) as $at => $item) {
            $items[] = is_array($item) ? self::item($item, PartWayIsUnreadable::item((int) $at)) : throw PartWayIsUnreadable::item((int) $at);
        }

        return PartWays::of(...$items);
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return PartWayEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<mixed> $item */
    private static function item(array $item, PartWayIsUnreadable $refused): APartWay
    {
        $year = Optional::number($item, WireField::Year, $refused);
        $holding = Holding::of(
            HoldingId::called(Required::text($item, WireField::Id, $refused)),
            Required::text($item, WireField::Title, $refused),
            Medium::tryFrom(Required::text($item, WireField::Medium, $refused)) ?? throw $refused,
            $year === null ? WhenItCameOut::unstated() : WhenItCameOut::in($year),
        );
        $reached = HowFarIn::at(Required::number($item, WireField::Position, $refused));
        $length = Optional::number($item, PartWayField::Length, $refused);
        $plays = Located::in($item, $refused);

        return $length === null ? APartWay::ofUnknownLength($holding, $reached, $plays) : APartWay::of($holding, $reached, $length, $plays);
    }
}
