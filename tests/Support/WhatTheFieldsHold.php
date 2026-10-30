<?php

declare(strict_types=1);

namespace Tests\Support;

use function data_get;
use function is_array;
use function is_string;

use Native\Mobile\Edge\NativeComponent;

use function str_ends_with;

/** What each text field a screen draws holds, in the order the fields are drawn, read off the tree the device is handed. */
final readonly class WhatTheFieldsHold
{
    /** The suffix every kind of text field's node type ends in. */
    private const string A_FIELD = '_text_input';

    /**
     * Render this screen the way the device renders it, and read each field it draws.
     *
     * @return list<string>
     */
    public static function on(NativeComponent $screen): array
    {
        return self::in(WhatTheDeviceWouldDraw::tree($screen));
    }

    /**
     * Each field at or under this node, what it holds and nothing where it holds nothing.
     *
     * @return list<string>
     */
    private static function in(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        $held = self::itself($node);
        $beneath = data_get($node, WhatTheDeviceWouldDraw::BENEATH);

        foreach (is_array($beneath) ? $beneath : [] as $child) {
            foreach (self::in($child) as $deeper) {
                $held[] = $deeper;
            }
        }

        return $held;
    }

    /**
     * What this node holds, where it is a field, and nothing where it is not.
     *
     * @param array<mixed> $node
     *
     * @return list<string>
     */
    private static function itself(array $node): array
    {
        $type = data_get($node, 'type');
        $value = data_get($node, 'props.value');

        if (! is_string($type) || ! str_ends_with($type, self::A_FIELD)) {
            return [];
        }

        return [is_string($value) ? $value : ''];
    }
}
