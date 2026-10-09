<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use InvalidArgumentException;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\FingerprintIsNotAFingerprint;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\LocationIsUnfit;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Sdk\Api\Fields\TitleField;
use Modules\Sdk\Api\WireField;

/**
 * Where one item streams from, read off the fields the core locates it with.
 *
 * The reason the core gives for stating no location comes first and is taken
 * as said. A location comes with the door's fingerprint or not at all: a
 * location with no certificate to pin is one no player may open, so it is
 * refused rather than handed on. An item with neither is one that does not
 * stream, as a series does not.
 */
final readonly class Located
{
    /** @param array<mixed> $item */
    public static function in(array $item, InvalidArgumentException $refused): WhereItPlays
    {
        $why = Optional::text($item, TitleField::Unlocated, $refused);

        if ($why !== '') {
            return WhereItPlays::cannot(Sentence::of($why));
        }

        $from = Optional::text($item, TitleField::StreamFrom, $refused);

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
