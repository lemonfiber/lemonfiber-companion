<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function array_values;
use function in_array;
use function is_array;
use function is_string;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\UI\Builders\Drawer;
use ReflectionObject;
use RuntimeException;

/**
 * The side menu and the list of stacks, told apart from the frame they are
 * drawn around.
 *
 * Both are the same on every screen about a stack, so what a frame draws is
 * read without them, and {@see WhatTheDeviceWouldDraw::inTheMenu()} and
 * {@see WhatTheDeviceWouldDraw::inTheListOfStacks()} read each on its own.
 */
final readonly class TheWaysAround
{
    /** The node type the menu arrives as. */
    private const string THE_MENU = 'native_drawer';

    /** The node types the list of stacks arrives as: the name that opens it, and the sheet it is. */
    private const array THE_LIST_OF_STACKS = ['top_bar_title', 'bottom_sheet'];

    /**
     * The menu, drawn against the screen the way the phone draws it.
     *
     * NativePHP renders it as a partial, without the frame's chrome, which would
     * carry the menu inside itself a second time.
     */
    public static function drawnOn(NativeComponent $screen, Drawer $menu): Element
    {
        $content = $menu->getContent();

        if ($content instanceof Element) {
            return $content;
        }

        $reflected = new ReflectionObject($screen);
        $reflected->getProperty('nativeCallbacks')->setValue($screen, new CallbackRegistry());

        $drawn = $reflected->getMethod('fromViewPartial')->invoke($screen, $content);

        return $drawn instanceof Element
            ? $drawn
            : throw new RuntimeException('The menu rendered something that is not an element.');
    }

    /**
     * These nodes, without the menu or the list of stacks among them.
     *
     * @param array<mixed> $nodes
     *
     * @return list<mixed>
     */
    public static function leftOutOf(array $nodes): array
    {
        $drawn = [];

        foreach ($nodes as $node) {
            if (self::isAWayAround($node)) {
                continue;
            }

            $drawn[] = $node;
        }

        return $drawn;
    }

    /**
     * What the list of stacks draws in a frame, the name that opens it first.
     *
     * @param array<mixed> $node
     *
     * @return list<mixed>
     */
    public static function theListOfStacksIn(array $node): array
    {
        $found = [];

        foreach (self::childrenOf($node) as $child) {
            $found = [...$found, ...self::theListOfStacksUnder($child)];
        }

        return $found;
    }

    /**
     * The list of stacks under a node: what it holds where it is part of the
     * list, and what its own children hold where it is not.
     *
     * @return list<mixed>
     */
    private static function theListOfStacksUnder(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        return in_array(self::typeOf($node), self::THE_LIST_OF_STACKS, strict: true)
            ? self::childrenOf($node)
            : self::theListOfStacksIn($node);
    }

    /**
     * @param array<mixed> $node
     *
     * @return list<mixed>
     */
    private static function childrenOf(array $node): array
    {
        return array_key_exists('children', $node) && is_array($node['children']) ? array_values($node['children']) : [];
    }

    private static function isAWayAround(mixed $node): bool
    {
        return is_array($node) && in_array(self::typeOf($node), [self::THE_MENU, ...self::THE_LIST_OF_STACKS], strict: true);
    }

    /** @param array<mixed> $node */
    private static function typeOf(array $node): string
    {
        return array_key_exists('type', $node) && is_string($node['type']) ? $node['type'] : '';
    }
}
