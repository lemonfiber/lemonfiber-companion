<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\GrantEnvelope;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\GrantIsUnfit;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SecondsIn;
use Modules\Sdk\Api\Fields\GrantField;
use Modules\Sdk\Internal\Wire;

use function preg_match;
use function sprintf;

/**
 * The `grant` envelope, read into the grant a device plays with.
 *
 * **The token is the whole of what is read, and it is required.** This app
 * asks for no rehearsal, so an answer with no token is not one a device can
 * play with, and it is refused rather than kept as a grant of nothing.
 *
 * **The grant lapses as the day it lasts until ends.** The core names the last
 * day it holds; read as the start of the day after, in UTC, a grant is never
 * taken to stand past the day the core named.
 */
final readonly class Grants
{
    /** A calendar day as the core writes one. */
    private const string A_DAY = '/\A\d{4}-\d{2}-\d{2}\z/';

    /** A day as the moment it starts, in the form {@see AMomentAsWritten} reads. */
    private const string AT_ITS_START = '%sT00:00:00Z';

    /**
     * @param Envelope<mixed> $envelope the `grant` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AGrant
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw GrantIsUnreadable::missing(WireField::Data);
        }

        try {
            return AGrant::of(self::text($data, GrantField::Token), self::lapsesAt(self::text($data, GrantField::LastsUntil)));
        } catch (GrantIsUnfit $why) {
            throw GrantIsUnreadable::unfit($why);
        }
    }

    /**
     * The payload as it arrived, before anything about its shape is believed.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return GrantEnvelope::in(Wire::checked($envelope))->data;
    }

    /** @param array<array-key, mixed> $data */
    private static function text(array $data, GrantField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw GrantIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /** The moment the day after the last day starts. */
    private static function lapsesAt(string $lastDay): Instant
    {
        if (preg_match(self::A_DAY, $lastDay) !== 1) {
            throw GrantIsUnreadable::missing(GrantField::LastsUntil);
        }

        // The arm that cannot read the day names the field that held it.
        $starts = AMomentAsWritten::of(sprintf(self::AT_ITS_START, $lastDay))->read(
            read: static fn(Instant $starts): Instant => $starts,
            unreadable: static fn(): GrantField => GrantField::LastsUntil,
        );

        return $starts instanceof Instant
            ? Instant::atEpochSeconds($starts->epochSeconds() + SecondsIn::ADay->value)
            : throw GrantIsUnreadable::missing($starts);
    }
}
