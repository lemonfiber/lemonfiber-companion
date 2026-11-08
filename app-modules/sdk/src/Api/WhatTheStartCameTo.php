<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\BootEnvelope;
use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\HowLongItHasFailed;
use Modules\Kernel\Api\HowTheCheckAfterAStartEnded;
use Modules\Kernel\Api\HowTheTunnelCameBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheCheckKept;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheTunnelAfterAStart;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Sdk\Api\Fields\BootField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `boot` envelope, as what the last start came to.
 *
 * The sibling of {@see WhatIsTold}, written the same way: a static fold with
 * no state, reading through {@see WireField}, and refusing rather than
 * salvaging anything the kernel would refuse.
 */
final readonly class WhatTheStartCameTo
{
    /**
     * When the machine last started, the checks kept, and how long the stack has failed to come back.
     *
     * @param Envelope<mixed> $envelope the `boot` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheLastStartCameTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StartIsUnreadable::missing(WireField::Data);
        }

        return WhatTheLastStartCameTo::said(
            self::moment($data, BootField::BootedAt),
            self::check($data, WireField::Verified),
            self::check($data, BootField::LastVerified),
            Optional::text($data, BootField::Declined, StartIsUnreadable::missing(BootField::Declined)),
            self::failing($data),
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
        return BootEnvelope::in($envelope)->data;
    }

    /**
     * A moment written in seconds since the Unix epoch, or none where nothing was said.
     *
     * @param array<mixed> $table
     */
    private static function moment(array $table, NamesAWireField $field): WhenItSays
    {
        $seconds = Optional::number($table, $field, StartIsUnreadable::missing($field));

        return $seconds === null ? WhenItSays::unsaid() : WhenItSays::at(Instant::atEpochSeconds($seconds));
    }

    /**
     * How many starts it has failed to come back after, and since when, or that it is not failing.
     *
     * @param array<mixed> $data
     */
    private static function failing(array $data): HowLongItHasFailed
    {
        $starts = Optional::number($data, BootField::FailedBoots, StartIsUnreadable::missing(BootField::FailedBoots));

        return $starts === null || $starts < 1
            ? HowLongItHasFailed::notFailing()
            : HowLongItHasFailed::after($starts, self::moment($data, BootField::FailingSince));
    }

    /**
     * One check after a start, or none where none is kept.
     *
     * @param array<mixed> $data
     */
    private static function check(array $data, NamesAWireField $field): TheCheckKept
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return TheCheckKept::none();
        }

        $check = $data[$field->value];

        if (! is_array($check)) {
            throw StartIsUnreadable::missing($field);
        }

        $ended = Required::text($check, WireField::Outcome, StartIsUnreadable::missing(WireField::Outcome));

        return TheCheckKept::this(ACheckAfterAStart::ran(
            Instant::atEpochSeconds(Required::number($check, WireField::At, StartIsUnreadable::missing(WireField::At))),
            HowTheCheckAfterAStartEnded::tryFrom($ended)
                ?? throw StartIsUnreadable::word(WireField::Outcome, $ended, ...array_map(static fn(HowTheCheckAfterAStartEnded $one): string => $one->value, HowTheCheckAfterAStartEnded::cases())),
            TheServicesNamed::of(...self::notBack($check)),
            self::tunnel($check),
            self::port($check),
            Optional::text($check, WireField::Said, StartIsUnreadable::missing(WireField::Said)),
        ));
    }

    /**
     * Each service that did not come back, none where none is said.
     *
     * @param  array<mixed> $check
     * @return list<string>
     */
    private static function notBack(array $check): array
    {
        if (! array_key_exists(BootField::NotBack->value, $check)) {
            return [];
        }

        $names = [];

        foreach (InTheOrderSent::rows(Required::rows($check, BootField::NotBack, StartIsUnreadable::missing(BootField::NotBack))) as $name) {
            $names[] = Required::isText($name) ? $name : throw StartIsUnreadable::missing(BootField::NotBack);
        }

        return $names;
    }

    /**
     * Whether the tunnel came back, by the state the check gives it.
     *
     * @param array<mixed> $check
     */
    private static function tunnel(array $check): TheTunnelAfterAStart
    {
        if (! array_key_exists(BootField::Tunnel->value, $check) || ! is_array($check[BootField::Tunnel->value])) {
            throw StartIsUnreadable::missing(BootField::Tunnel);
        }

        $tunnel = $check[BootField::Tunnel->value];
        $state = Required::text($tunnel, WireField::State, StartIsUnreadable::missing(WireField::State));

        return match (HowTheTunnelCameBack::tryFrom($state)) {
            HowTheTunnelCameBack::Held => TheTunnelAfterAStart::held(),
            HowTheTunnelCameBack::NotEstablished => TheTunnelAfterAStart::notEstablished(Required::text($tunnel, WireField::Said, StartIsUnreadable::missing(WireField::Said))),
            HowTheTunnelCameBack::NotChecked => TheTunnelAfterAStart::notChecked(),
            null => throw StartIsUnreadable::word(WireField::State, $state, ...array_map(static fn(HowTheTunnelCameBack $one): string => $one->value, HowTheTunnelCameBack::cases())),
        };
    }

    /**
     * The forwarded port before the start and after it, or that the stack forwards none.
     *
     * @param array<mixed> $check
     */
    private static function port(array $check): ThePortAfterAStart
    {
        if (! array_key_exists(WireField::Port->value, $check) || $check[WireField::Port->value] === null) {
            return ThePortAfterAStart::notForwarded();
        }

        $port = $check[WireField::Port->value];

        if (! is_array($port)) {
            throw StartIsUnreadable::missing(WireField::Port);
        }

        $was = Optional::number($port, WireField::Was, StartIsUnreadable::missing(WireField::Was));
        $now = Optional::number($port, WireField::Now, StartIsUnreadable::missing(WireField::Now));
        $taken = Required::flag($port, BootField::Pushed, StartIsUnreadable::missing(BootField::Pushed));

        return match (true) {
            $now === null => ThePortAfterAStart::unsaid(everyClientTakesIt: $taken),
            $was === null => ThePortAfterAStart::cameUpOn($now, everyClientTakesIt: $taken),
            $was === $now => ThePortAfterAStart::same($now, everyClientTakesIt: $taken),
            default => ThePortAfterAStart::moved($was, $now, everyClientTakesIt: $taken),
        };
    }
}
