<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Modules\Operator\Internal\Screens\Locked;
use Native\Mobile\Edge\Contracts\TreeObserver;
use Native\Mobile\Edge\NativeComponent;

/**
 * Tells the device when the lock screen has been published, and nothing else.
 *
 * The device keeps its cover over the window from the moment its lock stands
 * again until the lock screen is what is on the glass, so the frame from before
 * is never shown. The runloop hands every published frame to its observers
 * straight after publishing it, which is the first moment that is true; the
 * screen publishing it is the one driving the runloop.
 */
final readonly class TheLockIsOnTheGlass implements TreeObserver
{
    /** @param array<mixed> $tree */
    public function tree(array $tree, string $uri): void
    {
        $screen = NativeComponent::active();

        if ($screen instanceof Locked) {
            $screen->drawn();
        }
    }

    /** @param array<mixed> $event */
    public function event(array $event, ?string $label): void {}

    /** @param array<mixed> $payload */
    public function nav(array $payload): void {}
}
