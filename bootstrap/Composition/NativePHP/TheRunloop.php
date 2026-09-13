<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;

use function redirect;

/**
 * NativePHP's navigation runloop, which only a device can enter.
 *
 * The one piece of this composition that cannot run under test, kept apart so
 * that it is the *only* one. `NativeRouter::start()` blocks in the runloop
 * against the real bridge — with a live session that is a minute and a half of
 * reconnect spinning per request — so a suite that reached it would hang rather
 * than fail.
 *
 * Two statements, no branch, and nothing here decides anything: which screen,
 * which parameters, and what a test is answered with instead are all decided in
 * {@see ScreenRoutes}, against the {@see Runloop} seam, where they are driven.
 *
 * Named in `phpunit.xml`'s coverage exclusions with that reason, and
 * `TheDeviceOnlyListIsShortTest` is what stops the list growing quietly.
 */
final readonly class TheRunloop implements Runloop
{
    /**
     * @param Closure(string): mixed $build
     * @param array<mixed>           $params
     */
    public function enter(Closure $build, string $screen, array $params, string $path): mixed
    {
        // The runloop answers with a URI when the operator navigated away, and
        // with nothing when the screen was simply left.
        $exit = new ScreenRouter($build)->start($screen, $params, $path);

        return $exit === null ? '' : redirect($exit);
    }
}
