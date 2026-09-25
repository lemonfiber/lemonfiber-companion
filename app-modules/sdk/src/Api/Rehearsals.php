<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PreviewEnvelope;
use Modules\Kernel\Api\AProfileLeftOut;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheProfilesLeftOut;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Sdk\Api\Fields\PreviewField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `preview` envelope into what starting a form would come to.
 *
 * The services and the profiles left out are both required lists, and every
 * entry in them must be what the contract says; anything else is refused
 * with {@see RehearsalIsUnreadable}, never defaulted. A profile left out
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

        return WhatStartingItWouldComeTo::rehearsed(self::services($data), self::leftOut($data));
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
     * The services starting it would bring up.
     *
     * @param array<array-key, mixed> $data
     */
    private static function services(array $data): Services
    {
        $services = [];

        foreach (self::listed($data, WireField::Services) as $position => $named) {
            if (! is_string($named) || trim($named) === '') {
                throw RehearsalIsUnreadable::entry(WireField::Services, $position);
            }

            $services[] = ServiceId::called($named);
        }

        return $services === [] ? Services::none() : Services::these(...$services);
    }

    /**
     * The profiles it would leave out, each with what it would need.
     *
     * @param array<array-key, mixed> $data
     */
    private static function leftOut(array $data): TheProfilesLeftOut
    {
        $leftOut = [];

        foreach (self::listed($data, PreviewField::Dropped) as $position => $row) {
            $leftOut[] = self::aProfileLeftOut(is_array($row) ? $row : [], $position);
        }

        return TheProfilesLeftOut::of(...$leftOut);
    }

    /**
     * One profile left out, with what it would need.
     *
     * @param array<array-key, mixed> $row
     */
    private static function aProfileLeftOut(array $row, int $position): AProfileLeftOut
    {
        if (! array_key_exists(PreviewField::Profile->value, $row) || ! array_key_exists(PreviewField::Needs->value, $row)) {
            throw RehearsalIsUnreadable::entry(PreviewField::Dropped, $position);
        }

        $profile = $row[PreviewField::Profile->value];
        $needs = $row[PreviewField::Needs->value];

        if (! is_string($profile) || trim($profile) === '' || ! is_string($needs)) {
            throw RehearsalIsUnreadable::entry(PreviewField::Dropped, $position);
        }

        return AProfileLeftOut::needing(
            $profile,
            WhatItWouldNeed::tryFrom($needs) ?? throw RehearsalIsUnreadable::entry(PreviewField::Dropped, $position),
        );
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
