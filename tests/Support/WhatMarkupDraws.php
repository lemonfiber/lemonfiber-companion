<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;

use Illuminate\Support\Facades\Blade;

use function implode;
use function is_array;
use function is_string;
use function json_encode;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

use function sprintf;

/**
 * The tree the device would draw for a piece of markup, as one line: a
 * container is its type, its layout and its children in brackets, and a text
 * is its words.
 *
 * Read off a rendered tree rather than off the template, because where the
 * renderer puts an element is decided at render time: a component that wraps
 * `{{ $slot }}` in a column reads as if the slot were inside it, and draws the
 * slot beside it.
 *
 * Needs the application booted: a render uses the view factory, the component
 * namespaces and the precompiler.
 */
final readonly class WhatMarkupDraws
{
    public static function outline(string $markup): string
    {
        $was = NativeTagPrecompiler::setActive(active: true);

        try {
            NativeElementCollector::reset();
            Blade::render($markup);
            $tree = NativeElementCollector::collect();
        } finally {
            NativeTagPrecompiler::setActive(active: $was);
        }

        $id = 1;
        $emitted = [];
        $hashes = [];

        return self::outlineOf($tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes));
    }

    /** One node of the drawn tree, and everything under it, as one line. */
    private static function outlineOf(mixed $node): string
    {
        if (! is_array($node)) {
            return '';
        }

        $props = is_array($node['props'] ?? null) ? $node['props'] : [];

        if (is_string($props['text'] ?? null)) {
            return $props['text'];
        }

        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        return sprintf(
            '%s%s[%s]',
            is_string($node['type'] ?? null) ? $node['type'] : '',
            json_encode($node['layout'] ?? [], JSON_THROW_ON_ERROR),
            implode(', ', array_map(self::outlineOf(...), $children)),
        );
    }
}
