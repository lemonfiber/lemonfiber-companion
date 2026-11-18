<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use InvalidArgumentException;

use function is_string;

use Lemonfiber\Sdk\Generated\Kind;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsLastRun;
use Modules\Kernel\Api\TheLastLines;
use Modules\Sdk\Api\ScheduleIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * One run of a run-and-exit service as the wire carries it, refused naming the envelope it came on.
 */
final readonly class ARunRead
{
    /** @param array<mixed> $table */
    public static function in(array $table, Kind $kind): ItsLastRun
    {
        $started = Instant::atEpochSeconds(Required::number($table, WireField::Started, ScheduleIsUnreadable::aRun($kind, WireField::Started)));
        $ended = Instant::atEpochSeconds(Required::number($table, WireField::Ended, ScheduleIsUnreadable::aRun($kind, WireField::Ended)));
        if ($ended->isBefore($started)) {
            throw ScheduleIsUnreadable::aRun($kind, WireField::Ended);
        }

        $exit = Optional::number($table, WireField::Exit, ScheduleIsUnreadable::aRun($kind, WireField::Exit));
        $log = self::log($table, ScheduleIsUnreadable::aRun($kind, WireField::Log));

        return $exit === null ? ItsLastRun::endedWithoutACode($started, $ended, $log) : ItsLastRun::exited($started, $ended, $exit, $log);
    }

    /** @param array<mixed> $table */
    private static function log(array $table, InvalidArgumentException $refused): TheLastLines
    {
        $lines = [];

        foreach (Required::rows($table, WireField::Log, $refused) as $line) {
            $lines[] = is_string($line) ? $line : throw $refused;
        }

        return TheLastLines::these(...$lines);
    }
}
