<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\WatchEnvelope;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\WhatTheGuardSaw;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `watch` envelope, as what a guard did once it saw the data location go.
 *
 * The forms it was guarding, whether stopping them worked, and why it ended
 * are required, and every form must be named; anything else is refused with
 * {@see WatchIsUnreadable}, never defaulted. Whether stopping worked above all:
 * a guard read as having stopped the services when the stack did not say so
 * is a library shown as protected that was not.
 */
final readonly class Vigils
{
    /**
     * What the guard saw and did, as the stack reported it.
     *
     * @param Envelope<mixed> $envelope the `watch` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheGuardSaw
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw WatchIsUnreadable::missing(WireField::Data);
        }

        return WhatTheGuardSaw::of(
            Forms::these(...self::forms(Required::rows($data, WireField::Forms, WatchIsUnreadable::missing(WireField::Forms)))),
            Required::flag($data, WireField::Stopped, WatchIsUnreadable::missing(WireField::Stopped)),
            Required::text($data, WireField::Reason, WatchIsUnreadable::missing(WireField::Reason)),
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
        return WatchEnvelope::in($envelope)->data;
    }

    /**
     * Every form the guard was guarding, each required to be named.
     *
     * @param array<mixed> $listed
     * @return list<Form>
     */
    private static function forms(array $listed): array
    {
        if (! array_is_list($listed)) {
            throw WatchIsUnreadable::missing(WireField::Forms);
        }

        $forms = [];

        foreach ($listed as $position => $named) {
            if (! is_string($named) || trim($named) === '') {
                throw WatchIsUnreadable::form($position);
            }

            $forms[] = Form::called($named);
        }

        return $forms;
    }
}
