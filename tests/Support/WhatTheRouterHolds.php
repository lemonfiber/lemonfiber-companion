<?php

declare(strict_types=1);

namespace Tests\Support;

use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use ReflectionProperty;

/**
 * A router holding a screen over the paths beneath it, as the router holds one pushed over them.
 *
 * The stack is set by its paths alone: whether a screen lies over another is
 * a question about how deep the stack is, and building every screen beneath it
 * would ask each of them for what it draws first.
 */
final readonly class WhatTheRouterHolds
{
    /** Hand `$screen` a router holding it at `$itsPath`, over `$beneath`, the bottom first. */
    public static function over(NativeComponent $screen, string $itsPath, string ...$beneath): void
    {
        $entries = [];

        foreach ([...$beneath, $itsPath] as $uri) {
            $entries[] = ['uri' => $uri];
        }

        $router = new NativeRouter();
        new ReflectionProperty(NativeRouter::class, 'stack')->setValue($router, $entries);
        $screen->setRouter($router);
    }
}
