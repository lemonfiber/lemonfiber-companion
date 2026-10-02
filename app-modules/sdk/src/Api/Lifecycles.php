<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\LifecycleEnvelope;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\LifecycleField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\StackEditsSent;
use Modules\Sdk\Internal\WhatWasLeftOut;
use Modules\Sdk\Internal\Wire;
use Throwable;

use function trim;

/**
 * The `lifecycle` envelope, as what a start, a stop or a restart came to.
 *
 * Whether it was a rehearsal, where each service it waited for ended up, what
 * the plan left out and the ports it found held are required, as is the
 * command of a verb that ran; a start the stack declined ran nothing, so its
 * command is not read. Every entry must be what the contract says; anything
 * else is refused with {@see LifecycleIsUnreadable}, never defaulted. The
 * condition and the reason a start was declined are the contract's to leave
 * out, and are read only where it sent them. The ports found held default to
 * none, which is the contract's own default for a verb that starts nothing.
 */
final readonly class Lifecycles
{
    /**
     * What the verb came to, in the order the stack gave it.
     *
     * @param Envelope<mixed> $envelope the `lifecycle` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheVerbCameTo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw LifecycleIsUnreadable::missing(WireField::Data);
        }

        $was = WhetherItWasRehearsed::said(Required::flag($data, WireField::Rehearsed, LifecycleIsUnreadable::missing(WireField::Rehearsed)));
        $services = WhereTheServicesEndedUp::of(...self::services($data));
        $leftOut = self::leftOut(Required::rows($data, LifecycleField::Plan, LifecycleIsUnreadable::missing(LifecycleField::Plan)));
        $portsHeld = ThePortsHeld::of(...self::portsHeld($data));
        $edits = StackEditsSent::in($data, WireField::StackEdits);

        $report = self::carries($data, WireField::Held)
            ? WhatTheVerbCameTo::declined($was, $services, $leftOut, $portsHeld, Required::text($data, WireField::Held, LifecycleIsUnreadable::missing(WireField::Held)), $edits)
            : WhatTheVerbCameTo::reported($was, $services, $leftOut, $portsHeld, $edits, self::command($data));

        return self::condition($data, $report);
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
        return LifecycleEnvelope::in($envelope)->data;
    }

    /**
     * The exact command the stack ran, word by word.
     *
     * @param array<mixed> $data
     */
    private static function command(array $data): TheCommandLine
    {
        $words = [];
        $position = 0;

        foreach (Required::rows($data, WireField::Command, LifecycleIsUnreadable::missing(WireField::Command)) as $word) {
            $words[] = is_string($word) && trim($word) !== '' ? $word : throw LifecycleIsUnreadable::entry(WireField::Command, $position);
            $position++;
        }

        return $words === [] ? throw LifecycleIsUnreadable::missing(WireField::Command) : TheCommandLine::of(...$words);
    }

    /**
     * Where each service the verb waited for ended up.
     *
     * @param  array<mixed>               $data
     * @return list<WhereAServiceEndedUp>
     */
    private static function services(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, WireField::Services, LifecycleIsUnreadable::missing(WireField::Services)) as $row) {
            $refused = LifecycleIsUnreadable::entry(WireField::Services, $position);

            if (! is_array($row)) {
                throw $refused;
            }

            $found[] = WhereAServiceEndedUp::as(
                Required::text($row, WireField::Name, $refused),
                HowAServiceRuns::tryFrom(Required::text($row, WireField::State, $refused)) ?? throw $refused,
            );
            $position++;
        }

        return $found;
    }

    /**
     * The services the plan left out, each with what it needed.
     *
     * @param array<mixed> $plan
     */
    private static function leftOut(array $plan): TheServicesLeftOut
    {
        if (! array_key_exists(WireField::Filtered->value, $plan)) {
            throw LifecycleIsUnreadable::missing(WireField::Filtered);
        }

        return WhatWasLeftOut::in(
            $plan[WireField::Filtered->value],
            static fn(): Throwable => LifecycleIsUnreadable::missing(WireField::Filtered),
            static fn(int $position): Throwable => LifecycleIsUnreadable::entry(WireField::Filtered, $position),
        );
    }

    /**
     * Every port the verb wanted that something else already holds, and what holds it.
     *
     * @param  array<mixed>    $data
     * @return list<APortHeld>
     */
    private static function portsHeld(array $data): array
    {
        if (! array_key_exists(LifecycleField::PortConflicts->value, $data)) {
            return [];
        }

        $found = [];
        $position = 0;

        foreach (Required::rows($data, LifecycleField::PortConflicts, LifecycleIsUnreadable::missing(LifecycleField::PortConflicts)) as $row) {
            $refused = LifecycleIsUnreadable::entry(LifecycleField::PortConflicts, $position);

            if (! is_array($row)) {
                throw $refused;
            }

            $found[] = APortHeld::of(
                Required::number($row, WireField::Port, $refused),
                Required::text($row, WireField::WantedBy, $refused),
                Required::text($row, WireField::HeldBy, $refused),
            );
            $position++;
        }

        return $found;
    }

    /**
     * The report, with what the stack says those services amount to where it said.
     *
     * @param array<mixed> $data
     */
    private static function condition(array $data, WhatTheVerbCameTo $report): WhatTheVerbCameTo
    {
        if (! self::carries($data, WireField::Condition)) {
            return $report;
        }

        $refused = LifecycleIsUnreadable::missing(WireField::Condition);

        return $report->amountingTo(HowTheStackIsRunning::tryFrom(Required::text($data, WireField::Condition, $refused)) ?? throw $refused);
    }

    /**
     * Whether a field the contract may leave out, or send as nothing, was sent as something.
     *
     * @param array<mixed> $data
     */
    private static function carries(array $data, NamesAWireField $field): bool
    {
        return array_key_exists($field->value, $data) && $data[$field->value] !== null;
    }
}
