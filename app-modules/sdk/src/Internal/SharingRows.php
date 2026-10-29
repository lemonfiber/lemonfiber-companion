<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\AnAdapterWritingItsUpstream;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * Every adapter an install would have write the files of the service it fronts, as the install's account carries them.
 */
final readonly class SharingRows
{
    /**
     * Each one, in the stack's order, or none where a stack says nothing of it.
     *
     * @param  array<mixed>                      $install
     * @return list<AnAdapterWritingItsUpstream>
     */
    public static function in(array $install): array
    {
        if (! array_key_exists(PluginsField::Sharing->value, $install)) {
            return [];
        }

        if (! is_array($install[PluginsField::Sharing->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Sharing);
        }

        $found = [];
        $position = 0;

        foreach ($install[PluginsField::Sharing->value] as $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Sharing, PluginsField::Adapter, $position);
            }

            $found[] = AnAdapterWritingItsUpstream::by(
                self::text($row, PluginsField::Adapter, $position),
                self::text($row, WireField::Upstream, $position),
                self::text($row, WireField::At, $position),
            );
            $position++;
        }

        return $found;
    }

    /** @param array<mixed> $row */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw PluginsAreUnreadable::entry(PluginsField::Sharing, $field, $position);
        }

        return $row[$field->value];
    }
}
