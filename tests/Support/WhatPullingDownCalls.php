<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_pop;
use function array_values;
use function data_get;
use function is_array;
use function is_int;

use Native\Mobile\Edge\NativeComponent;

/**
 * The callbacks a frame hands the device for being pulled down, one per container that can be.
 */
final readonly class WhatPullingDownCalls
{
    /** @return list<int> */
    public static function on(NativeComponent $screen): array
    {
        $pulled = [];
        $waiting = [WhatTheDeviceWouldDraw::tree($screen)];

        while ($waiting !== []) {
            $node = array_pop($waiting);

            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? null) === 'refreshable') {
                $pulled[] = data_get($node, 'props.on_refresh');
            }

            foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
                $waiting[] = $child;
            }
        }

        return array_values(array_filter($pulled, is_int(...)));
    }
}
