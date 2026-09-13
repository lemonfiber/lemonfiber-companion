<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;

/**
 * Running one screen until the operator leaves it.
 *
 * A seam rather than a call, for the reason every native capability in this
 * application has one: the thing behind it cannot run in a suite.
 * {@see TheRunloop} enters NativePHP's own runloop, which blocks against the
 * real bridge — so a test that reached it would hang rather than fail, which is
 * the worst way for a test to go wrong.
 *
 * With the seam, the file that cannot be covered is two statements long and
 * everything that decides anything sits in {@see ScreenRoutes} on this side of
 * it, where a test drives it. Without one, the deciding and the blocking are
 * the same method and neither can be checked.
 */
interface Runloop
{
    /**
     * Run `$screen` until it is left, and answer what to do next.
     *
     * Takes how to build a screen rather than a router, so that nothing on the
     * calling side has to name the class that enters the loop.
     *
     * @param Closure(string): mixed $build  how a screen is made, given its name
     * @param array<mixed>           $params what the route carried
     */
    public function enter(Closure $build, string $screen, array $params, string $path): mixed;
}
