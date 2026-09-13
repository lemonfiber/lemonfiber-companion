<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;

/**
 * The navigation stack, building each screen through the container.
 *
 * NativePHP's own router builds a screen with `new $class` — no container, and
 * `mount()` takes no arguments — so a screen has no way to be given a port.
 * Every screen after the first reads something, so without this the choice is a
 * screen that reaches the container itself, which is the service location `A3`
 * refuses and for the reason `A3` gives: a class that reaches the container
 * stops telling the truth about what it needs.
 *
 * One method is overridden and the other three lines are the parent's, in the
 * parent's order. That is deliberate: a screen still gets its router, its route
 * parameters and its navigation data exactly as NativePHP hands them over, and
 * the only difference is where the object came from.
 *
 * **In the composition root because that is what this is.** Deciding how a
 * screen meets the ports it needs is the one thing this directory exists for.
 *
 * It takes *how to build a screen* rather than the container itself, which is
 * not a formality: a router holding a container could resolve anything, and the
 * rule against a container parameter is written because that is what a class
 * holding one eventually does. This one can make a screen and nothing else.
 *
 * **Delete this when NativePHP resolves a component itself.** Its own test says
 * so and fails when the reason stops being true, so the fork cannot outlive the
 * gap it was written for.
 */
final class ScreenRouter extends NativeRouter
{
    /**
     * @param Closure(string): mixed $build how a screen is made, given its name
     */
    public function __construct(private readonly Closure $build) {}

    /**
     * A screen, with whatever it declared in its constructor.
     *
     * `make()` rather than `new`, which is the whole of this class. A screen
     * with no constructor parameters is built identically either way, so this
     * changes nothing for the screens that need nothing.
     *
     * @param array<mixed> $params
     * @param array<mixed> $data
     */
    protected function createComponent(string $class, array $params = [], array $data = []): NativeComponent
    {
        $component = ($this->build)($class);

        if (! $component instanceof NativeComponent) {
            throw ScreenIsNotAScreen::named($class);
        }

        $component->setRouter($this);
        $component->setParams($params);
        $component->setData($data);

        return $component;
    }
}
