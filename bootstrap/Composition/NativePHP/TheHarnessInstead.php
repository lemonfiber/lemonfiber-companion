<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;

use function response;
use function sprintf;

/**
 * What a request for a screen is answered with when there is no device.
 *
 * A suite must never enter the navigation runloop: it blocks against the real
 * bridge, so a test that reached it would hang rather than fail — which is the
 * worst way for a test to go wrong, because a hang has no message.
 *
 * NativePHP guards that with an `if` inside its route closure. This is the same
 * guard as a {@see Runloop} of its own, which is not merely a tidier spelling:
 * the branch it replaces was the reason nothing on the other side of it could
 * be covered, and *whether there is a device* is a fact about the composition
 * rather than about routing. The composition root decides it once, where every
 * other such decision is already made.
 *
 * It says where to test the screen, because a 200 with nothing in it is what a
 * passing smoke test looks like when the route is wrong.
 *
 * **It builds the screen before answering, and that is the point of doing this
 * here rather than returning a constant.** A route naming a screen that cannot
 * be constructed — a typo, a port with no binding — would otherwise answer 200
 * to every smoke test and fail on a handset. Building it is what a device does
 * first, so it is what this does first; the screen is then dropped, because
 * running it is the part that needs a device.
 */
final readonly class TheHarnessInstead implements Runloop
{
    /**
     * A route that exists, answered without running anything.
     *
     * `200` means *this route is registered and names a screen* and nothing
     * about the screen itself, which is what `D6` asks the number to say.
     */
    private const int THE_ROUTE_IS_THERE = 200;

    /**
     * @param Closure(string): mixed $build
     * @param array<mixed>           $params
     */
    public function enter(Closure $build, string $screen, array $params, string $path): mixed
    {
        // Built and dropped. What this proves is that the route names something
        // this application can construct, which is the half of a screen a
        // machine with no handset can check.
        $build($screen);

        return response(
            sprintf('Native screen [%s] — test it with Native::test() / Native::visit().', $screen),
            self::THE_ROUTE_IS_THERE,
        );
    }
}
