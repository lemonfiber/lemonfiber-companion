<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ServedEnvelope;
use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheCertificateItPresents;
use Modules\Kernel\Api\TheNamesItCovers;
use Modules\Sdk\Api\Fields\ServedField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `served` envelope, as how a stack serves its web surface.
 */
final readonly class TheServedRead
{
    /** @param Envelope<mixed> $envelope the `served` envelope, as the client returned it */
    public static function in(Envelope $envelope): HowItIsServed
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ServedIsUnreadable::missing(WireField::Data);
        }

        $port = Required::count($data, WireField::Port, ServedIsUnreadable::missing(WireField::Port));
        $network = Required::flag($data, ServedField::Network, ServedIsUnreadable::missing(ServedField::Network));
        $named = Optional::text($data, WireField::Named, ServedIsUnreadable::missing(WireField::Named));
        $unrecorded = Optional::text($data, ServedField::Unrecorded, ServedIsUnreadable::missing(ServedField::Unrecorded));

        if (! Required::flag($data, ServedField::Encrypted, ServedIsUnreadable::missing(ServedField::Encrypted))) {
            return HowItIsServed::plainly($port, $network, $named, $unrecorded);
        }

        return HowItIsServed::presenting(self::certificate($data), $port, $network, $named, $unrecorded);
    }

    /** @param array<mixed> $data */
    private static function certificate(array $data): TheCertificateItPresents
    {
        $certificate = array_key_exists(WireField::Certificate->value, $data) && is_array($data[WireField::Certificate->value])
            ? $data[WireField::Certificate->value]
            : throw ServedIsUnreadable::missing(WireField::Certificate);

        return TheCertificateItPresents::said(
            Fingerprint::of(Required::text($certificate, WireField::Fingerprint, ServedIsUnreadable::missing(WireField::Fingerprint))),
            self::name($certificate, ServedField::Subject, ServedField::SubjectCut),
            self::name($certificate, ServedField::Issuer, ServedField::IssuerCut),
            Required::flag($certificate, ServedField::SelfSigned, ServedIsUnreadable::missing(ServedField::SelfSigned)),
            TheNamesItCovers::these(
                Required::count($certificate, ServedField::NamesMore, ServedIsUnreadable::missing(ServedField::NamesMore)),
                ...self::names($certificate),
            ),
            Instant::atEpochSeconds(Required::number($certificate, ServedField::ValidFrom, ServedIsUnreadable::missing(ServedField::ValidFrom))),
            Instant::atEpochSeconds(Required::number($certificate, ServedField::ValidUntil, ServedIsUnreadable::missing(ServedField::ValidUntil))),
        );
    }

    /** @param array<mixed> $certificate */
    private static function name(array $certificate, ServedField $text, ServedField $cut): ACertificateName
    {
        if (! array_key_exists($text->value, $certificate) || ! is_string($certificate[$text->value])) {
            throw ServedIsUnreadable::missing($text);
        }

        return ACertificateName::said($certificate[$text->value], Required::flag($certificate, $cut, ServedIsUnreadable::missing($cut)));
    }

    /**
     * @param  array<mixed>          $certificate
     * @return list<ACertificateName>
     */
    private static function names(array $certificate): array
    {
        $names = [];

        foreach (Required::rows($certificate, ServedField::Names, ServedIsUnreadable::missing(ServedField::Names)) as $name) {
            $names[] = is_string($name) ? ACertificateName::said($name, cut: false) : throw ServedIsUnreadable::missing(ServedField::Names);
        }

        return $names;
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
        return ServedEnvelope::in($envelope)->data;
    }
}
