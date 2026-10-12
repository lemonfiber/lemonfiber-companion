<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_filter;
use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\BandwidthEnvelope;
use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\ARateGiven;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatALimitCameTo;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhichRate;
use Modules\Sdk\Api\Fields\BandwidthField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\HowAClientAnswered;
use Modules\Sdk\Internal\HowALiftStood;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `bandwidth` envelope a run of the line's action answered with: the line, what the run did, each client's answer, and where a lift stands.
 *
 * The line itself is {@see HowTheLineIs}' reading; this reads what that one
 * leaves to the surface that changes the line. Anything the kernel would
 * refuse is refused whole with {@see BandwidthIsUnreadable}.
 */
final readonly class WhatTheLineCameTo
{
    /**
     * The report, refused whole where any part of it cannot be read.
     *
     * @param Envelope<mixed> $envelope the `bandwidth` envelope a finished job answered with
     */
    public static function in(Envelope $envelope): TheLineAsChanged
    {
        $data = self::payload($envelope);
        $clients = [];

        foreach (Required::rows($data, WireField::Clients, BandwidthIsUnreadable::missing(WireField::Clients->value)) as $position => $row) {
            $clients[] = self::client($row, APath::beneath(WireField::Clients->value, $position));
        }

        return TheLineAsChanged::said(
            HowTheLineIs::in($envelope),
            WhatTheLineRunDid::said(
                Required::flag($data, WireField::Rehearsed, BandwidthIsUnreadable::missing(WireField::Rehearsed->value)),
                Required::flag($data, WireField::Applied, BandwidthIsUnreadable::missing(WireField::Applied->value)),
            ),
            self::lift($data),
            ...$clients,
        );
    }

    /**
     * The payload, as it actually arrived, for {@see HowTheLineIs::payload()}'s reason.
     *
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function payload(Envelope $envelope): array
    {
        $data = self::data(Wire::checked($envelope));

        if (! is_array($data)) {
            throw BandwidthIsUnreadable::missing(WireField::Data->value);
        }

        return $data;
    }

    /** @param Envelope<mixed> $envelope */
    private static function data(Envelope $envelope): mixed
    {
        return BandwidthEnvelope::in($envelope)->data;
    }

    private static function client(mixed $row, string $where): AClientsHolding
    {
        if (! is_array($row)) {
            throw BandwidthIsUnreadable::missing($where);
        }

        $client = Required::text($row, WireField::Client, BandwidthIsUnreadable::missing(APath::beneath($where, WireField::Client->value)));
        $pulling = self::pulling($row, APath::beneath($where, BandwidthField::Pulling->value));
        $answerAt = APath::beneath($where, BandwidthField::Answer->value);
        $answer = Required::rows($row, BandwidthField::Answer, BandwidthIsUnreadable::missing($answerAt));
        $shape = Required::text($answer, BandwidthField::Answered, BandwidthIsUnreadable::missing(APath::beneath($answerAt, BandwidthField::Answered->value)));

        return match (HowAClientAnswered::tryFrom($shape)) {
            HowAClientAnswered::Held => AClientsHolding::answered(
                $client,
                $pulling,
                self::direction($answer, BandwidthField::Down, $answerAt),
                self::direction($answer, BandwidthField::Up, $answerAt),
            ),
            HowAClientAnswered::Silent => AClientsHolding::silent($client, $pulling, Required::text($answer, WireField::Said, BandwidthIsUnreadable::missing(APath::beneath($answerAt, WireField::Said->value)))),
            null => throw BandwidthIsUnreadable::word(APath::beneath($answerAt, BandwidthField::Answered->value), $shape, ...array_map(static fn(HowAClientAnswered $case): string => $case->value, HowAClientAnswered::cases())),
        };
    }

    /** @param array<mixed> $answer */
    private static function direction(array $answer, BandwidthField $direction, string $answerAt): ADirectionHeld
    {
        $where = APath::beneath($answerAt, $direction->value);
        $held = Required::rows($answer, $direction, BandwidthIsUnreadable::missing($where));
        $verdictAt = APath::beneath($where, WireField::Verdict->value);
        $verdict = Required::text($held, WireField::Verdict, BandwidthIsUnreadable::missing($verdictAt));

        $rates = array_filter([
            self::rate($held, $where, WireField::Asked, WhichRate::Asked),
            self::rate($held, $where, BandwidthField::Accepted, WhichRate::Accepted),
            self::rate($held, $where, BandwidthField::Moving, WhichRate::Moving),
        ]);

        return ADirectionHeld::said(
            WhatALimitCameTo::tryFrom($verdict) ?? throw BandwidthIsUnreadable::word($verdictAt, $verdict, ...array_map(static fn(WhatALimitCameTo $case): string => $case->value, WhatALimitCameTo::cases())),
            ...$rates,
        );
    }

    /** @param array<mixed> $held */
    private static function rate(array $held, string $where, NamesAWireField $field, WhichRate $which): ?ARateGiven
    {
        $given = Optional::number($held, $field, BandwidthIsUnreadable::missing(APath::beneath($where, $field->value)));

        return $given === null ? null : ARateGiven::of($which, $given);
    }

    /** @param array<mixed> $row */
    private static function pulling(array $row, string $where): WhatADownloadClientSaid
    {
        $said = Optional::text($row, BandwidthField::Pulling, BandwidthIsUnreadable::missing($where));

        return match ($said) {
            '' => WhatADownloadClientSaid::Unsaid,
            WhatADownloadClientSaid::Fetching->value => WhatADownloadClientSaid::Fetching,
            WhatADownloadClientSaid::Stopped->value => WhatADownloadClientSaid::Stopped,
            default => throw BandwidthIsUnreadable::word($where, $said, WhatADownloadClientSaid::Fetching->value, WhatADownloadClientSaid::Stopped->value),
        };
    }

    /** @param array<mixed> $data */
    private static function lift(array $data): WhereALiftStands
    {
        $respite = Required::rows($data, BandwidthField::Respite, BandwidthIsUnreadable::missing(BandwidthField::Respite->value));
        $standingAt = WireField::Standing->under(BandwidthField::Respite);
        $standing = Required::text($respite, WireField::Standing, BandwidthIsUnreadable::missing($standingAt));
        $says = Optional::text($data, BandwidthField::RespiteSays, BandwidthIsUnreadable::missing(BandwidthField::RespiteSays->value));

        return match (HowALiftStood::tryFrom($standing)) {
            HowALiftStood::None => WhereALiftStands::none(),
            HowALiftStood::InForce => WhereALiftStands::inForce(self::seconds($respite), $says),
            HowALiftStood::Expired => WhereALiftStands::expired(self::seconds($respite), $says),
            null => throw BandwidthIsUnreadable::word($standingAt, $standing, ...array_map(static fn(HowALiftStood $case): string => $case->value, HowALiftStood::cases())),
        };
    }

    /** @param array<mixed> $respite */
    private static function seconds(array $respite): int
    {
        return Required::number($respite, WireField::Seconds, BandwidthIsUnreadable::missing(WireField::Seconds->under(BandwidthField::Respite)));
    }
}
