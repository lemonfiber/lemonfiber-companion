<?php

declare(strict_types=1);

namespace Tests\Support;

use function __;
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
    /** @param array<string, mixed> $data what the markup is rendered with */
    public static function outline(string $markup, array $data = []): string
    {
        return self::outlineOf(self::drawn($markup, $data));
    }

    /**
     * The tree the device would draw for a piece of markup, as the renderer
     * hands it over: types, layouts and props, for what an outline leaves out.
     *
     * @param array<string, mixed> $data what the markup is rendered with
     *
     * @return array<array-key, mixed>
     */
    public static function drawn(string $markup, array $data = []): array
    {
        $was = NativeTagPrecompiler::setActive(active: true);

        try {
            NativeElementCollector::reset();
            Blade::render($markup, $data);
            $tree = NativeElementCollector::collect();
        } finally {
            NativeTagPrecompiler::setActive(active: $was);
        }

        $id = 1;
        $emitted = [];
        $hashes = [];

        return $tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes);
    }

    /**
     * The words a translation key draws as a text in the outline, and the key
     * itself where the catalogue has none, as a screen would draw it.
     */
    public static function words(string $key): string
    {
        $said = __($key);

        return is_string($said) ? $said : $key;
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
