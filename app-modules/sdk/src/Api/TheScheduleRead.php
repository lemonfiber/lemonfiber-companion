<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StatusEnvelope;
use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheLastLines;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Sdk\Api\Fields\StatusField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `status` envelope, as its run-and-exit services.
 */
final readonly class TheScheduleRead
{
    /**
     * Every run-and-exit service, in the order the stack listed them.
     *
     * @param Envelope<mixed> $envelope the `status` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheScheduledServices
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ScheduleIsUnreadable::missing(WireField::Data);
        }

        $services = [];

        foreach (Required::rows($data, StatusField::Scheduled, ScheduleIsUnreadable::missing(StatusField::Scheduled)) as $row) {
            $services[] = self::service(self::table($row, StatusField::Scheduled));
        }

        return TheScheduledServices::these(...$services);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return StatusEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $table */
    private static function service(array $table): ARunAndExit
    {
        $id = ServiceId::called(Required::text($table, WireField::Id, ScheduleIsUnreadable::missing(WireField::Id)));
        $name = Required::text($table, WireField::Name, ScheduleIsUnreadable::missing(WireField::Name));
        $running = Required::flag($table, WireField::Running, ScheduleIsUnreadable::missing(WireField::Running));
        $fired = Required::flag($table, StatusField::Fired, ScheduleIsUnreadable::missing(StatusField::Fired));
        $skipped = Required::count($table, StatusField::Skipped, ScheduleIsUnreadable::missing(StatusField::Skipped));
        $health = self::health($table);

        if ($health === HowItsLastRunWent::NotRun) {
            return ARunAndExit::notRunYet($id, $name, $running, $fired, $skipped);
        }

        return ARunAndExit::lastRan($id, $name, $health, self::run(Required::rows($table, WireField::Last, ScheduleIsUnreadable::missing(WireField::Last))), $running, $fired, $skipped);
    }

    /** @param array<mixed> $table */
    private static function health(array $table): HowItsLastRunWent
    {
        $said = Required::text($table, WireField::Health, ScheduleIsUnreadable::missing(WireField::Health));

        return HowItsLastRunWent::tryFrom($said) ?? throw ScheduleIsUnreadable::word(WireField::Health, $said, ...array_map(static fn(HowItsLastRunWent $one): string => $one->value, HowItsLastRunWent::cases()));
    }

    /** @param array<mixed> $table */
    private static function run(array $table): ItsLastRun
    {
        $started = Instant::atEpochSeconds(Required::number($table, WireField::Started, ScheduleIsUnreadable::missing(WireField::Started)));
        $ended = Instant::atEpochSeconds(Required::number($table, WireField::Ended, ScheduleIsUnreadable::missing(WireField::Ended)));
        if ($ended->isBefore($started)) {
            throw ScheduleIsUnreadable::missing(WireField::Ended);
        }

        $exit = Optional::number($table, WireField::Exit, ScheduleIsUnreadable::missing(WireField::Exit));
        $log = self::log($table);

        return $exit === null ? ItsLastRun::endedWithoutACode($started, $ended, $log) : ItsLastRun::exited($started, $ended, $exit, $log);
    }

    /** @param array<mixed> $table */
    private static function log(array $table): TheLastLines
    {
        $lines = [];

        foreach (Required::rows($table, StatusField::Log, ScheduleIsUnreadable::missing(StatusField::Log)) as $line) {
            $lines[] = is_string($line) ? $line : throw ScheduleIsUnreadable::missing(StatusField::Log);
        }

        return TheLastLines::these(...$lines);
    }

    /** @return array<mixed> */
    private static function table(mixed $row, NamesAWireField $field): array
    {
        return is_array($row) ? $row : throw ScheduleIsUnreadable::missing($field);
    }
}
