<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function count;
use function is_array;

use Modules\Kernel\Api\WhereAServiceKeepsIt;
use Modules\Sdk\Api\Fields\MigrationField;
use Modules\Sdk\Api\MigrationIsUnreadable;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

/**
 * Where a service the survey found keeps its configuration or its library, read off the one arm the stack tagged it with.
 */
final readonly class WhereAFoundServiceKeepsThings
{
    /** @param array<mixed> $service */
    public static function in(array $service, NamesAWireField $field, MigrationIsUnreadable $refused): WhereAServiceKeepsIt
    {
        if (! array_key_exists($field->value, $service) || ! is_array($service[$field->value]) || count($service[$field->value]) !== 1) {
            throw $refused;
        }

        $place = $service[$field->value];

        if (array_key_exists(WireField::At->value, $place)) {
            return WhereAServiceKeepsIt::at(Required::text($place, WireField::At, $refused));
        }

        if (array_key_exists(WireField::Unread->value, $place)) {
            return WhereAServiceKeepsIt::unread(Required::text($place, WireField::Unread, $refused));
        }

        return WhereAServiceKeepsIt::none(Required::text($place, MigrationField::KeepsNone, $refused));
    }
}
