<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use InvalidArgumentException;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\LocationIsUnfit;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Sdk\Api\WireField;

/**
 * Where one item streams from, read off the fields the core locates it with.
 *
 * The obstacle the core names for stating no location comes first, and its
 * sentence is taken as said. A location comes with the door's fingerprint or
 * not at all: a location with no certificate to pin is one no player may open,
 * so it is refused rather than handed on. An item with neither is one that does not
 * stream, as a series does not.
 */
final readonly class Located
{
    /** @param array<mixed> $item */
    public static function in(array $item, InvalidArgumentException $refused): WhereItPlays
    {
        if (array_key_exists(WireField::Unlocated->value, $item) && $item[WireField::Unlocated->value] !== null) {
            return WhereItPlays::cannot(WhatEachObstacleSaid::of($item[WireField::Unlocated->value], $refused));
        }

        $from = Optional::text($item, WireField::StreamFrom, $refused);

        if ($from === '') {
            return WhereItPlays::doesNotStream();
        }

        $door = Required::rows($item, WireField::Door, $refused);

        try {
            return WhereItPlays::at(Location::of($from), Fingerprint::of(Required::text($door, WireField::Fingerprint, $refused)));
        } catch (LocationIsUnfit|FingerprintIsNotAFingerprint) {
            throw $refused;
        }
    }
}
