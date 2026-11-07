<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;

use Modules\Kernel\Api\AFileReshipped;
use Modules\Kernel\Api\APlaceholder;
use Modules\Kernel\Api\APlaceholderForm;
use Modules\Kernel\Api\TheFilesReshipped;
use Modules\Kernel\Api\ThePlaceholders;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * Starting files' placeholders, as an install's changes and an update's account carry them: by the secret's id and its form, never a value.
 */
final readonly class PlaceholderRows
{
    /**
     * The placeholders a change's starting file names, none where the stack says nothing of them.
     *
     * @param array<mixed> $change
     */
    public static function ofAChange(array $change, int $position): ThePlaceholders
    {
        return array_key_exists(PluginsField::Placeholders->value, $change)
            ? self::under($change, PluginsField::Placeholders, WireField::Changes, $position)
            : ThePlaceholders::these();
    }

    /**
     * Each starting file an update changes the placeholders of, none where the stack says nothing of them.
     *
     * @param array<mixed> $update
     */
    public static function reshippedBy(array $update): TheFilesReshipped
    {
        if (! array_key_exists(PluginsField::Reshipped->value, $update)) {
            return TheFilesReshipped::these();
        }

        $found = [];

        foreach (InTheOrderSent::rows(Required::rows($update, PluginsField::Reshipped, PluginsAreUnreadable::missing(PluginsField::Reshipped))) as $position => $row) {
            $refused = PluginsAreUnreadable::entry(PluginsField::Reshipped, WireField::Path, $position);

            if (! is_array($row)) {
                throw $refused;
            }

            $found[] = AFileReshipped::at(
                Required::text($row, WireField::Path, $refused),
                self::under($row, WireField::Was, PluginsField::Reshipped, $position),
                self::under($row, PluginsField::Becomes, PluginsField::Reshipped, $position),
            );
        }

        return TheFilesReshipped::these(...$found);
    }

    /**
     * One list of placeholders, refused where any of it is not one.
     *
     * @param array<mixed> $row
     */
    private static function under(array $row, NamesAWireField $field, NamesAWireField $list, int $position): ThePlaceholders
    {
        $refused = PluginsAreUnreadable::entry($list, $field, $position);
        $found = [];

        foreach (InTheOrderSent::rows(Required::rows($row, $field, $refused)) as $placeholder) {
            if (! is_array($placeholder)) {
                throw $refused;
            }

            $found[] = APlaceholder::naming(
                Required::text($placeholder, WireField::Secret, $refused),
                APlaceholderForm::tryFrom(Required::text($placeholder, PluginsField::Form, $refused)) ?? throw $refused,
            );
        }

        return ThePlaceholders::these(...$found);
    }
}
