<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\ScopeIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * A copy's `scope`, read the same way wherever a copy is described.
 *
 * A copy taken and a copy put back both say what they cover, in one shape,
 * so one reader reads it for both. Every field is required, and a scope word
 * this app has no case for is refused rather than read as the whole stack.
 * A blank name is the kernel's to refuse, and the adapter that asked catches
 * it.
 */
final readonly class Scopes
{
    /**
     * The scope the table carries under `scope`.
     *
     * @param array<array-key, mixed> $data
     */
    public static function in(array $data): ScopeOfACopy
    {
        $scope = self::table($data, WireField::Scope);
        $which = WhichScope::tryFrom(self::text($scope, WireField::Scope))
            ?? throw ScopeIsUnreadable::missing(WireField::Scope);

        return match ($which) {
            WhichScope::WholeStack => ScopeOfACopy::theWholeStack(),
            WhichScope::Service => ScopeOfACopy::oneService(ServiceId::called(self::text($scope, WireField::Name))),
            WhichScope::Existing => ScopeOfACopy::anExistingSetup(
                AnExistingSetup::of(self::text($scope, WireField::Project), self::trees($scope)),
            ),
        };
    }

    /**
     * Where each tree of an existing setup was read from, in order.
     *
     * The path inside the archive is not read: it says where a tree sits in
     * the file, which an operator with no filesystem in front of them cannot
     * use.
     *
     * @param array<array-key, mixed> $scope
     */
    private static function trees(array $scope): WhatACopyHolds
    {
        if (! array_key_exists(WireField::Trees->value, $scope)) {
            throw ScopeIsUnreadable::missing(WireField::Trees);
        }

        $trees = $scope[WireField::Trees->value];

        if (! is_array($trees) || ! array_is_list($trees)) {
            throw ScopeIsUnreadable::missing(WireField::Trees);
        }

        $paths = [];

        foreach ($trees as $position => $tree) {
            if (! is_array($tree) || ! array_key_exists(WireField::HostPath->value, $tree) || ! is_string($tree[WireField::HostPath->value])) {
                throw ScopeIsUnreadable::tree($position);
            }

            $paths[] = $tree[WireField::HostPath->value];
        }

        return WhatACopyHolds::these(...$paths);
    }

    /**
     * A table the payload must carry.
     *
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw ScopeIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A field the scope must carry, as text.
     *
     * @param array<array-key, mixed> $scope
     */
    private static function text(array $scope, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $scope) || ! is_string($scope[$field->value])) {
            throw ScopeIsUnreadable::missing($field);
        }

        return $scope[$field->value];
    }
}
