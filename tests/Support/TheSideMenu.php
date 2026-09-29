<?php

declare(strict_types=1);

namespace Tests\Support;

use function in_array;
use function is_array;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\UI\Builders\Drawer;
use ReflectionObject;
use RuntimeException;

/**
 * The side menu NativePHP hangs beside a frame, told apart from the frame.
 *
 * It is the same on every screen about a stack, so what a frame draws is read
 * without it, and {@see WhatTheDeviceWouldDraw::inTheMenu()} reads it on its
 * own.
 */
final readonly class TheSideMenu
{
    /** The node type the menu arrives as. */
    private const string DRAWN_AS = 'native_drawer';

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
     * These nodes, without the menu among them.
     *
     * @param array<mixed> $nodes
     *
     * @return list<mixed>
     */
    public static function leftOutOf(array $nodes): array
    {
        $drawn = [];

        foreach ($nodes as $node) {
            if (is_array($node) && in_array(self::DRAWN_AS, $node, strict: true)) {
                continue;
            }

            $drawn[] = $node;
        }

        return $drawn;
    }
}
