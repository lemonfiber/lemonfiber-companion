<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\StackEditsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * Stack files the operator edited, read into the kernel's shape.
 *
 * One reader for the list wherever it arrives: the files a reset reverts, and
 * the files a start or an update leaves as the operator set them, are the one
 * contract type, a path and the diff against lemonfiber's own. The caller names
 * where the list sits in its envelope, and everything this refuses, it refuses
 * as {@see StackEditsAreUnreadable}.
 */
final readonly class StackEditsSent
{
    /**
     * The list under `$field`, in the stack's order.
     *
     * @param array<array-key, mixed> $data
     */
    public static function in(array $data, NamesAWireField $field): TheStackEdits
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value]) || ! array_is_list($data[$field->value])) {
            throw StackEditsAreUnreadable::missing($field);
        }

        $edits = [];

        foreach ($data[$field->value] as $position => $edit) {
            $edits[] = self::edit($edit, $position);
        }

        return TheStackEdits::these(...$edits);
    }

    /** One file: its path, and the lines where it differs. */
    private static function edit(mixed $edit, int $position): AStackEdit
    {
        if (! is_array($edit)
            || ! array_key_exists(WireField::Path->value, $edit) || ! is_string($edit[WireField::Path->value])
            || ! array_key_exists(WireField::Diff->value, $edit) || ! is_string($edit[WireField::Diff->value])) {
            throw StackEditsAreUnreadable::entry($position);
        }

        return AStackEdit::at($edit[WireField::Path->value], $edit[WireField::Diff->value]);
    }
}
