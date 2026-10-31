<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;

use InvalidArgumentException;

use function is_array;
use function is_string;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\KindOfThingWaitedFor;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Sdk\Api\WireField;

/**
 * The services a stack holds back until the operator gives what they need to start, as every envelope that carries `waiting` lists them.
 *
 * `status`, `settings` and a plugin install carry the same list, so one reader
 * reads it; each hands the refusal of its own envelope, thrown where any part
 * of the list cannot be read.
 */
final readonly class TheWaitingRead
{
    /**
     * Every service the table lists as held back, in the stack's order.
     *
     * @param array<mixed> $table the table that carries `waiting`
     */
    public static function in(array $table, InvalidArgumentException $refused): TheServicesWaiting
    {
        $found = [];

        foreach (self::listed(Required::rows($table, WireField::Waiting, $refused), $refused) as $row) {
            $found[] = self::service(self::table($row, $refused), $refused);
        }

        return TheServicesWaiting::these(...$found);
    }

    /** @param array<mixed> $row */
    private static function service(array $row, InvalidArgumentException $refused): AServiceWaiting
    {
        $wants = [];

        foreach (self::listed(Required::rows($row, WireField::Wants, $refused), $refused) as $want) {
            $wants[] = self::want(self::table($want, $refused), $refused);
        }

        return AServiceWaiting::for(ServiceId::called(Required::text($row, WireField::Service, $refused)), ...$wants);
    }

    /** @param array<mixed> $want */
    private static function want(array $want, InvalidArgumentException $refused): WhatAServiceWaitsFor
    {
        return match (KindOfThingWaitedFor::tryFrom(Required::text($want, WireField::Kind, $refused))) {
            KindOfThingWaitedFor::Setting => WhatAServiceWaitsFor::aSetting(Required::text($want, WireField::Setting, $refused), ...self::choices($want, $refused)),
            KindOfThingWaitedFor::Secret => WhatAServiceWaitsFor::aSecret(Required::text($want, WireField::Secret, $refused)),
            KindOfThingWaitedFor::Service => WhatAServiceWaitsFor::aService(ServiceId::called(Required::text($want, WireField::Service, $refused))),
            null => throw $refused,
        };
    }

    /**
     * The values a setting waited for accepts, or none where it lists none.
     *
     * @param  array<mixed> $want
     * @return list<string>
     */
    private static function choices(array $want, InvalidArgumentException $refused): array
    {
        if (! array_key_exists(WireField::Choices->value, $want) || $want[WireField::Choices->value] === null) {
            return [];
        }

        $choices = [];

        foreach (self::listed(self::table($want[WireField::Choices->value], $refused), $refused) as $choice) {
            $choices[] = is_string($choice) ? $choice : throw $refused;
        }

        return $choices;
    }

    /** @return array<mixed> */
    private static function table(mixed $row, InvalidArgumentException $refused): array
    {
        return is_array($row) ? $row : throw $refused;
    }

    /**
     * @param  array<mixed> $rows
     * @return list<mixed>
     */
    private static function listed(array $rows, InvalidArgumentException $refused): array
    {
        return array_is_list($rows) ? $rows : throw $refused;
    }
}
