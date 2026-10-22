<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;

use InvalidArgumentException;

use function is_array;

use Modules\Kernel\Api\AMoveInPlace;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheMovesInPlace;
use Modules\Kernel\Api\WhatBecameOfAMoveInPlace;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

/**
 * Moves in place, read into the kernel's shape.
 *
 * On offer on a start and on an update, and as an update tried them, each
 * refused with the refusal the caller hands it.
 */
final readonly class MovesInPlaceSent
{
    /**
     * The moves on offer, in the stack's order.
     *
     * @param array<array-key, mixed> $data
     */
    public static function offered(array $data, InvalidArgumentException $refused): TheMovesInPlace
    {
        if (! self::carries($data, WireField::Moves)) {
            return TheMovesInPlace::none();
        }

        $listed = $data[WireField::Moves->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw $refused;
        }

        $moves = [];

        foreach ($listed as $row) {
            if (! is_array($row)) {
                throw $refused;
            }

            $moves[] = self::move($row, $refused);
        }

        return TheMovesInPlace::these(...$moves);
    }

    /**
     * What became of each move an update tried, in the stack's order.
     *
     * @param array<array-key, mixed> $data
     */
    public static function tried(array $data, InvalidArgumentException $refused): WhatBecameOfTheMovesInPlace
    {
        if (! self::carries($data, WireField::Moved)) {
            return WhatBecameOfTheMovesInPlace::none();
        }

        $listed = $data[WireField::Moved->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw $refused;
        }

        $tried = [];

        foreach ($listed as $row) {
            if (! is_array($row)) {
                throw $refused;
            }

            $tried[] = Required::flag($row, WireField::Taken, $refused)
                ? WhatBecameOfAMoveInPlace::taken(self::move($row, $refused))
                : WhatBecameOfAMoveInPlace::notTaken(self::move($row, $refused), Optional::text($row, WireField::Why, $refused));
        }

        return WhatBecameOfTheMovesInPlace::these(...$tried);
    }

    /**
     * Whether the list under `$field` was sent, which it is not where it is empty.
     *
     * @param array<array-key, mixed> $data
     */
    private static function carries(array $data, NamesAWireField $field): bool
    {
        return array_key_exists($field->value, $data) && $data[$field->value] !== null;
    }

    /**
     * One move: the stack's own service, and the plugin that would take its place.
     *
     * @param array<array-key, mixed> $row
     */
    private static function move(array $row, InvalidArgumentException $refused): AMoveInPlace
    {
        return AMoveInPlace::of(
            ServiceId::called(Required::text($row, WireField::Service, $refused)),
            APluginId::called(Required::text($row, WireField::Plugin, $refused)),
        );
    }
}
