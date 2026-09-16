<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use Modules\Dx\Api\AStackThatIsNotThere;
use Modules\Dx\Api\StandsIn;

/**
 * Everything this module can take the place of.
 *
 * `Q-R72` asks for one place local-only affordances live and for that place to
 * admit a new one without any release artefact changing. This is the first
 * half; {@see StandsIn} is the second. Adding one is a class in `Api/`
 * implementing that interface and a line here — and nothing outside
 * `app-modules/dx` is touched, which is the part that matters: a release
 * installs without this module, so a new affordance cannot change what ships
 * even by accident.
 *
 * **A list rather than a scan of the directory.** Discovering implementations
 * would mean reading the filesystem or reflecting over classes, and `B3` and
 * `P4` both refuse that — rightly, because a registry that finds its own
 * entries is a registry whose contents depend on what happened to be autoloaded
 * at the moment it was asked. A line is also a place a reviewer looks.
 *
 * Instances rather than class names, so nothing here has to build anything from
 * a string. What each one replaces is the instance's own answer.
 */
final readonly class TheStandIns
{
    /**
     * Every stand-in, in no particular order.
     *
     * Each replaces a different port, so there is no order for them to be in —
     * and were two ever to name the same port, the container would take the
     * last and say nothing. `EveryStandInReplacesItsOwnPortTest` is what
     * refuses that rather than a comment asking for care.
     *
     * @return list<StandsIn<object>>
     */
    public static function all(): array
    {
        return [
            new AStackThatIsNotThere(),
        ];
    }
}
