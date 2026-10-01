<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Modules\Operator\Api\NotingWhereTheOperatorIs;
use Native\Mobile\Edge\Contracts\TreeObserver;
use Native\Mobile\Edge\NativeComponent;

/**
 * Hands the operator each screen that comes to the front, once per screen.
 *
 * The runloop hands every published frame to its observers, and a screen
 * publishes many; a frame on the route the last one was on is the same screen,
 * so it is passed over before anything is read.
 */
final class TheOperatorIsHere implements TreeObserver
{
    private string $lastRoute = '';

    public function __construct(private readonly NotingWhereTheOperatorIs $noting) {}

    /** @param array<mixed> $tree */
    public function tree(array $tree, string $uri): void
    {
        if ($uri === $this->lastRoute) {
            return;
        }

        $this->lastRoute = $uri;
        $screen = NativeComponent::active();

        if ($screen instanceof NativeComponent) {
            $this->noting->cameToTheFront($screen);
        }
    }

    /** @param array<mixed> $event */
    public function event(array $event, ?string $label): void {}

    /** @param array<mixed> $payload */
    public function nav(array $payload): void {}
}
