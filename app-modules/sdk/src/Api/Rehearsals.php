<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PreviewEnvelope;
use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Sdk\Api\Fields\PreviewField;
use Modules\Sdk\Internal\WhatWasLeftOut;
use Modules\Sdk\Internal\Wire;
use Throwable;

use function trim;

/**
 * Reads the `preview` envelope into what starting a form would come to.
 *
 * The services, the services left out and the footprint are all required,
 * and every entry must be what the contract says; anything else is refused
 * with {@see RehearsalIsUnreadable}, never defaulted. A service left out
 * needing something this app has no case for is refused too, because a
 * reason read as some other reason sends an operator to the wrong setting.
 */
final readonly class Rehearsals
{
    /**
     * The rehearsal, in the order the stack gave it.
     *
     * @param Envelope<mixed> $envelope the `preview` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatStartingItWouldComeTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw RehearsalIsUnreadable::missing(WireField::Data);
        }

        if (! array_key_exists(WireField::Filtered->value, $data)) {
            throw RehearsalIsUnreadable::missing(WireField::Filtered);
        }

        return WhatStartingItWouldComeTo::rehearsed(
            self::services($data, WireField::Services),
            WhatWasLeftOut::in(
                $data[WireField::Filtered->value],
                static fn(int $position): Throwable => $position < 0
                    ? RehearsalIsUnreadable::missing(WireField::Filtered)
                    : RehearsalIsUnreadable::entry(WireField::Filtered, $position),
            ),
            self::footprint($data),
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
        return PreviewEnvelope::in($envelope)->data;
    }

    /**
     * A list of services the payload must carry.
     *
     * @param array<array-key, mixed> $data
     */
    private static function services(array $data, NamesAWireField $field): Services
    {
        $services = [];

        foreach (self::listed($data, $field) as $position => $named) {
            if (! is_string($named) || trim($named) === '') {
                throw RehearsalIsUnreadable::entry($field, $position);
            }

            $services[] = ServiceId::called($named);
        }

        return $services === [] ? Services::none() : Services::these(...$services);
    }

    /**
     * What the stack estimates starting would take, and what it could not estimate.
     *
     * @param array<array-key, mixed> $data
     */
    private static function footprint(array $data): AFootprint
    {
        if (! array_key_exists(PreviewField::Footprint->value, $data) || ! is_array($data[PreviewField::Footprint->value])) {
            throw RehearsalIsUnreadable::missing(PreviewField::Footprint);
        }

        $footprint = $data[PreviewField::Footprint->value];

        if (! array_key_exists(PreviewField::EstimatedMib->value, $footprint)) {
            throw RehearsalIsUnreadable::missing(PreviewField::EstimatedMib);
        }

        $mebibytes = $footprint[PreviewField::EstimatedMib->value];

        if (! is_int($mebibytes) || $mebibytes < 0) {
            throw RehearsalIsUnreadable::missing(PreviewField::EstimatedMib);
        }

        return AFootprint::estimated($mebibytes, self::services($footprint, PreviewField::Unestimated));
    }

    /**
     * A list the payload must carry.
     *
     * @param array<array-key, mixed> $data
     * @return list<mixed>
     */
    private static function listed(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw RehearsalIsUnreadable::missing($field);
        }

        $listed = $data[$field->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw RehearsalIsUnreadable::missing($field);
        }

        return $listed;
    }
}
