<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Bootstrap\Composition\NativePHP\Runloop;
use Closure;

use function count;
use function expect;

/**
 * A runloop that runs nothing and remembers what it was asked to.
 *
 * Written out rather than mocked — `G1` — so a change to the seam fails to
 * compile here instead of drifting.
 *
 * **It builds the screen and drops it, which is what the other two do.**
 * Running a screen needs a device and is the part this stands in for; building
 * one does not, and both shipped implementations do it first for a reason that
 * belongs to the port rather than to either of them — a route naming a screen
 * this application cannot construct answers 200 to anything that skips the
 * build, and fails on a handset. Three tests drive a route to a screen through
 * this, so a stand-in that skipped the build is three routes proved reachable
 * over a screen nobody checked could be made.
 */
final class ARunloopThatOnlyRemembers implements Runloop
{
    /** @var list<array{screen: string, params: array<mixed>, path: string}> */
    private array $entered = [];

    /**
     * @param Closure(string): mixed $build
     * @param array<mixed>           $params
     */
    public function enter(Closure $build, string $screen, array $params, string $path): mixed
    {
        // Built and dropped, as `TheHarnessInstead` builds and drops it. What
        // is remembered is what a test asks about; what is built is what the
        // contract holds every implementation of this seam to.
        $build($screen);

        $this->entered[] = ['screen' => $screen, 'params' => $params, 'path' => $path];

        return '';
    }

    /**
     * What it was last asked to run.
     *
     * A method rather than public properties, which the analyser refuses on a
     * non-readonly class — and it reads better anyway: a stand-in that answers
     * questions is one a test can be written against without knowing how it
     * remembers.
     *
     * @return array{screen: string, params: array<mixed>, path: string}
     */
    public function whatItRan(): array
    {
        expect($this->entered)->not->toBeEmpty('the runloop was never entered');

        return $this->entered[count($this->entered) - 1];
    }
}
