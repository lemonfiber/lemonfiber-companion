<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\WatcherEnvelope;
use Modules\Kernel\Api\AHeartbeat;
use Modules\Kernel\Api\AMonitor;
use Modules\Kernel\Api\AMonitorNotSetUp;
use Modules\Kernel\Api\HowAMonitorStands;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfMonitor;
use Modules\Kernel\Api\WhatAMonitorCovers;
use Modules\Kernel\Api\WhatKindOfThingIsCovered;
use Modules\Kernel\Api\WhatNoMonitorCovers;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Kernel\Api\WhichWayItLooks;
use Modules\Sdk\Api\Fields\WatcherField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `watcher` envelope, as what the outside watcher sees.
 *
 * The sibling of {@see WhatTheStartCameTo}, written the same way.
 */
final readonly class WhatTheWatcherSaw
{
    /**
     * Whether the watcher answers, every monitor it holds, and what no active monitor covers.
     *
     * @param Envelope<mixed> $envelope the `watcher` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatTheWatcherSees
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw WatcherIsUnreadable::missing(WireField::Data);
        }

        $watcher = Optional::text($data, WatcherField::Watcher, WatcherIsUnreadable::missing(WatcherField::Watcher));
        $readAt = Instant::atEpochSeconds(Required::number($data, WatcherField::ReadAt, WatcherIsUnreadable::missing(WatcherField::ReadAt)));
        $things = [];

        foreach (Optional::rows($data, WatcherField::Uncovered, WatcherIsUnreadable::missing(WatcherField::Uncovered)) as $thing) {
            $things[] = self::covers($thing);
        }

        $uncovered = WhatNoMonitorCovers::these(...$things);

        if (! Required::flag($data, WatcherField::Reachable, WatcherIsUnreadable::missing(WatcherField::Reachable))) {
            return WhatTheWatcherSees::blind($watcher, Required::text($data, WatcherField::UnreachableBecause, WatcherIsUnreadable::missing(WatcherField::UnreachableBecause)), $readAt, $uncovered);
        }

        $monitors = [];

        foreach (Optional::rows($data, WatcherField::Monitors, WatcherIsUnreadable::missing(WatcherField::Monitors)) as $row) {
            $monitors[] = self::monitor($row);
        }

        return WhatTheWatcherSees::answering($watcher, $readAt, $uncovered, ...$monitors);
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
        return WatcherEnvelope::in($envelope)->data;
    }

    /** One monitor, with what it covers, the heartbeat it waits for and why it could not be set up, where each is said. */
    private static function monitor(mixed $row): AMonitor
    {
        if (! is_array($row)) {
            throw WatcherIsUnreadable::missing(WatcherField::Monitors);
        }

        $kind = Required::text($row, WireField::Kind, WatcherIsUnreadable::missing(WireField::Kind));
        $state = Required::text($row, WireField::State, WatcherIsUnreadable::missing(WireField::State));
        $monitor = AMonitor::named(
            Required::text($row, WireField::Name, WatcherIsUnreadable::missing(WireField::Name)),
            KindOfMonitor::tryFrom($kind) ?? throw WatcherIsUnreadable::word(WireField::Kind, $kind, ...array_map(static fn(KindOfMonitor $one): string => $one->value, KindOfMonitor::cases())),
            HowAMonitorStands::tryFrom($state) ?? throw WatcherIsUnreadable::word(WireField::State, $state, ...array_map(static fn(HowAMonitorStands $one): string => $one->value, HowAMonitorStands::cases())),
            Instant::atEpochSeconds(Required::number($row, WatcherField::Since, WatcherIsUnreadable::missing(WatcherField::Since))),
            Optional::instant($row, WatcherField::LastReading, WatcherIsUnreadable::missing(WatcherField::LastReading)),
        );
        $monitor = Required::flag($row, WireField::Active, WatcherIsUnreadable::missing(WireField::Active)) ? $monitor : $monitor->switchedOff();

        $covers = self::table($row, WireField::Covers);
        $monitor = $covers === null ? $monitor : $monitor->covering(self::covers($covers));
        $path = self::path($row);
        $monitor = $path instanceof WhichWayItLooks ? $monitor->reachedBy($path) : $monitor;
        $heartbeat = self::table($row, WatcherField::Heartbeat);
        $monitor = $heartbeat === null ? $monitor : $monitor->waitingFor(self::heartbeat($heartbeat));
        $unconfigured = self::table($row, WatcherField::Unconfigured);

        return $unconfigured === null ? $monitor : $monitor->notSetUp(AMonitorNotSetUp::said(
            Required::text($unconfigured, WireField::Wanted, WatcherIsUnreadable::missing(WireField::Wanted)),
            Required::text($unconfigured, WatcherField::Watches, WatcherIsUnreadable::missing(WatcherField::Watches)),
            Required::text($unconfigured, WireField::Why, WatcherIsUnreadable::missing(WireField::Why)),
        ));
    }

    /** The one thing a monitor covers, by the one key it is written under. */
    private static function covers(mixed $covers): WhatAMonitorCovers
    {
        if (! is_array($covers)) {
            throw WatcherIsUnreadable::missing(WireField::Covers);
        }

        if (array_key_exists(WireField::Service->value, $covers)) {
            return WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Service, Required::text($covers, WireField::Service, WatcherIsUnreadable::missing(WireField::Service)));
        }

        if (array_key_exists(WatcherField::EntryPoint->value, $covers)) {
            return WhatAMonitorCovers::of(WhatKindOfThingIsCovered::EntryPoint, Required::text($covers, WatcherField::EntryPoint, WatcherIsUnreadable::missing(WatcherField::EntryPoint)));
        }

        if (array_key_exists(WireField::Job->value, $covers)) {
            return WhatAMonitorCovers::of(WhatKindOfThingIsCovered::Job, Required::text($covers, WireField::Job, WatcherIsUnreadable::missing(WireField::Job)));
        }

        throw WatcherIsUnreadable::missing(WireField::Covers);
    }

    /**
     * Which way a monitor reaches what it covers, or null where it does not say.
     *
     * @param array<mixed> $row
     */
    private static function path(array $row): ?WhichWayItLooks
    {
        $path = Optional::text($row, WireField::Path, WatcherIsUnreadable::missing(WireField::Path));

        return $path === '' ? null : WhichWayItLooks::tryFrom($path) ?? throw WatcherIsUnreadable::word(WireField::Path, $path, ...array_map(static fn(WhichWayItLooks $one): string => $one->value, WhichWayItLooks::cases()));
    }

    /**
     * A heartbeat, by how often it is due, its grace and when it last arrived.
     *
     * @param array<mixed> $heartbeat
     */
    private static function heartbeat(array $heartbeat): AHeartbeat
    {
        return AHeartbeat::due(
            HowLong::ofSeconds(Required::number($heartbeat, WatcherField::EveryS, WatcherIsUnreadable::missing(WatcherField::EveryS))),
            HowLong::ofSeconds(Required::number($heartbeat, WireField::GraceS, WatcherIsUnreadable::missing(WireField::GraceS))),
            Optional::instant($heartbeat, WatcherField::LastCheckIn, WatcherIsUnreadable::missing(WatcherField::LastCheckIn)),
        );
    }

    /**
     * An object a row may hold, or null where it is absent or null.
     *
     * @param  array<mixed>      $table
     * @return array<mixed>|null
     */
    private static function table(array $table, NamesAWireField $field): ?array
    {
        if (! array_key_exists($field->value, $table) || $table[$field->value] === null) {
            return null;
        }

        return is_array($table[$field->value]) ? $table[$field->value] : throw WatcherIsUnreadable::missing($field);
    }
}
