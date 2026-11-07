<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\PluginLines;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\WireField;

use function sprintf;

/** Each secret a recipe of a plugin's captures, read from what the plugin declares, by the name the stack's store keeps it under. */
final readonly class SecretsCaptured
{
    /** What separates a namespace from a secret's id in the name the stack's store keeps it under. */
    private const string NAMESPACED = ':';

    /**
     * Each one the record names; a record that declares nothing captures nothing.
     *
     * @param array<mixed> $row
     */
    public static function by(array $row, string $plugin): PluginLines
    {
        $found = [];

        foreach (self::declared($row) as $secret) {
            if (is_array($secret) && self::captures($secret) && array_key_exists(WireField::Id->value, $secret) && is_string($secret[WireField::Id->value])) {
                $found[] = sprintf('%s%s%s', $plugin, self::NAMESPACED, $secret[WireField::Id->value]);
            }
        }

        return PluginLines::under(PluginsField::Captured->value, ...$found);
    }

    /**
     * Every secret the record declares.
     *
     * @param  array<mixed> $row
     * @return array<mixed>
     */
    private static function declared(array $row): array
    {
        if (! array_key_exists(PluginsField::Declared->value, $row) || ! is_array($row[PluginsField::Declared->value])) {
            return [];
        }

        $declared = $row[PluginsField::Declared->value];

        return array_key_exists(WireField::Secrets->value, $declared) && is_array($declared[WireField::Secrets->value])
            ? $declared[WireField::Secrets->value]
            : [];
    }

    /**
     * Whether a recipe captures this one's value; absent is none does.
     *
     * @param array<mixed> $secret
     */
    private static function captures(array $secret): bool
    {
        return array_key_exists(PluginsField::Captured->value, $secret)
            && is_bool($secret[PluginsField::Captured->value])
            && $secret[PluginsField::Captured->value];
    }
}
