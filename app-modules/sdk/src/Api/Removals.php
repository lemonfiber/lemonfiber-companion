<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\RemovalEnvelope;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhatTheRemovalFound;
use Modules\Sdk\Api\Fields\RemovalField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `removal` envelope into what taking somebody out cost, or did.
 *
 * Written the way {@see Invitations} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see RemovalIsUnreadable}.
 *
 * **`confirmed` decides which constructor answers**, so a reading nobody
 * agreed to is never read as somebody taken out. **How far it reached is read
 * as the stack wrote it**, whatever `confirmed` says beside it: a reach this
 * app worked out from the other would be a second opinion about whether
 * somebody is still in the household.
 */
final readonly class Removals
{
    /**
     * The removal, as the stack answered it.
     *
     * @param Envelope<mixed> $envelope the `removal` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ARemoval
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw RemovalIsUnreadable::missing(WireField::Data);
        }

        $who = SomebodyInTheHousehold::called(self::text($data, WireField::Name));
        $requests = self::requests($data);
        $asks = self::yesOrNo($data, RemovalField::AsksThroughTheRequestService);
        $revoked = self::revoked($data);
        $findings = WhatTheRemovalFound::of(...self::findings($data));

        return self::yesOrNo($data, WireField::Confirmed)
            ? ARemoval::carriedOut($who, $requests, $asks, $revoked, $findings)
            : ARemoval::described($who, $requests, $asks, $revoked, $findings);
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
        return RemovalEnvelope::in($envelope)->data;
    }

    /**
     * How many of their requests go with them.
     *
     * @param array<mixed> $data
     */
    private static function requests(array $data): int
    {
        if (! array_key_exists(WireField::Requests->value, $data) || ! is_int($data[WireField::Requests->value])) {
            throw RemovalIsUnreadable::missing(WireField::Requests);
        }

        return $data[WireField::Requests->value];
    }

    /**
     * How far it reached, from the word the stack wrote.
     *
     * @param array<mixed> $data
     */
    private static function revoked(array $data): HowFarTheRemovalReached
    {
        $said = self::text($data, RemovalField::Revoked);

        return HowFarTheRemovalReached::tryFrom($said)
            ?? throw RemovalIsUnreadable::word(RemovalField::Revoked, $said, ...array_map(static fn(HowFarTheRemovalReached $case): string => $case->value, HowFarTheRemovalReached::cases()));
    }

    /**
     * What it found, each in the stack's words.
     *
     * @param  array<mixed> $data
     * @return list<string>
     */
    private static function findings(array $data): array
    {
        if (! array_key_exists(WireField::Findings->value, $data) || ! is_array($data[WireField::Findings->value])) {
            throw RemovalIsUnreadable::missing(WireField::Findings);
        }

        $said = [];

        foreach ($data[WireField::Findings->value] as $finding) {
            if (! is_string($finding) || trim($finding) === '') {
                throw RemovalIsUnreadable::missing(WireField::Findings);
            }

            $said[] = $finding;
        }

        return $said;
    }

    /**
     * A yes or no the envelope carries.
     *
     * @param array<mixed> $data
     */
    private static function yesOrNo(array $data, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw RemovalIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A required text of the envelope.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw RemovalIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
