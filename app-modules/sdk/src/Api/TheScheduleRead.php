<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StatusEnvelope;
use Modules\Kernel\Api\ARunAndExit;
use Modules\Kernel\Api\HowItsLastRunWent;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Sdk\Api\Fields\StatusField;
use Modules\Sdk\Internal\ARunRead;
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

        return ARunAndExit::lastRan($id, $name, $health, ARunRead::in(Required::rows($table, WireField::Last, ScheduleIsUnreadable::missing(WireField::Last)), StatusEnvelope::KIND), $running, $fired, $skipped);
    }

    /** @param array<mixed> $table */
    private static function health(array $table): HowItsLastRunWent
    {
        $said = Required::text($table, WireField::Health, ScheduleIsUnreadable::missing(WireField::Health));

        return HowItsLastRunWent::tryFrom($said) ?? throw ScheduleIsUnreadable::word(WireField::Health, $said, ...array_map(static fn(HowItsLastRunWent $one): string => $one->value, HowItsLastRunWent::cases()));
    }

    /** @return array<mixed> */
    private static function table(mixed $row, NamesAWireField $field): array
    {
        return is_array($row) ? $row : throw ScheduleIsUnreadable::missing($field);
    }
}
