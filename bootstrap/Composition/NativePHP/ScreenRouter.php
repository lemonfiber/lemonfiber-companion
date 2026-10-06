<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;

use function end;

use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Events\Screen\ScreenResumed;

/**
 * The navigation stack, building each screen through the container.
 *
 * NativePHP's own router builds a screen with `new $class` — no container, and
 * `mount()` takes no arguments — so a screen has no way to be given a port.
 * Every screen after the first reads something, so without this the choice is a
 * screen that reaches the container itself, and a class that reaches the
 * container stops telling the truth about what it needs.
 *
 * The other three lines of the building are the parent's, in the parent's
 * order. That is deliberate: a screen still gets its router, its route
 * parameters and its navigation data exactly as NativePHP hands them over, and
 * the only difference is where the object came from.
 *
 * It also hands each screen that comes to the front to `$atTheFront`: one just
 * built, before its placeholder is drawn, and one uncovered again, before it
 * runs. That is where the theme a screen is drawn in is chosen, and the loop
 * itself is the parent's.
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
     * @param Closure(string, array<mixed>): mixed $build      how a screen is made, given its name and its route's parameters
     * @param Closure(NativeComponent): void        $atTheFront what a screen coming to the front is handed to
     */
    public function __construct(
        private readonly Closure $build,
        private readonly Closure $atTheFront,
    ) {}

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
        $component = ($this->build)($class, $params);

        if (! $component instanceof NativeComponent) {
            throw ScreenIsNotAScreen::named($class);
        }

        $component->setRouter($this);
        $component->setParams($params);
        $component->setData($data);

        ($this->atTheFront)($component);

        return $component;
    }

    /**
     * The parent's announcement, with the screen uncovered again handed on first.
     *
     * A screen comes back to the front when the one over it is left, and the
     * parent announces that before the screen runs again: the one moment
     * between the two where nothing has been drawn yet.
     */
    protected function announce(object $event): void
    {
        $top = end($this->stack);

        if ($event instanceof ScreenResumed && $top !== false) {
            ($this->atTheFront)($top['component']);
        }

        parent::announce($event);
    }
}
