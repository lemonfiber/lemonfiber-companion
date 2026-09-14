<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;
use function sprintf;

/**
 * How often a screen looks again, and the one place each cadence is decided.
 *
 * `N1-R27` says a screen whose content can change while it is open refreshes on
 * a **stated** cadence and does not rely on the operator leaving and returning
 * to see a change. Two halves, and the second is what this type exists for: a
 * screen that refreshes silently is a screen an operator cannot reason about —
 * they do not know whether what they are looking at is a second old or a minute
 * old, and the one thing they came to find out is whether something has
 * changed.
 *
 * **The number is a constant so the attribute and the sentence cannot
 * disagree.** `#[Poll]` takes a constant expression, and a screen that polled
 * every two seconds while telling somebody it looks every five would be stating
 * a cadence that is not the one it keeps. Both read from here.
 *
 * **This is not in tension with `N1-R17`.** That rule refuses a screen that
 * polls to fill in its own fields — four connections to draw one frame, on a
 * home network, to a machine that may be asleep. This is the narrow opposite
 * case: work the stack is already carrying out, where the answer genuinely
 * changes without anybody touching the phone, and where *ask again* as the only
 * road means an operator tapping a button to find out whether a thing they
 * started has finished. A cadence that runs only while the work runs is the
 * whole of what `N1-R27` asks for and no more.
 */
enum HowOften: string
{
    /**
     * While a stack is carrying out a repair the operator agreed to.
     *
     * The only content in this app that changes on its own. Five seconds
     * because a repair takes seconds to minutes and an operator is watching
     * the screen while it runs — long enough not to hammer a machine on a home
     * network, short enough that *it finished* arrives while they are still
     * looking.
     */
    case WhileWorkRuns = 'while_work_runs';

    /** The interval, in the milliseconds `#[Poll]` counts. */
    public const int WHILE_WORK_RUNS_MS = 5_000;

    /** A thousand of them to the second, which is the only unit an operator reads. */
    private const int A_SECOND = 1_000;

    /** How long this cadence is, in the milliseconds the platform counts. */
    public function milliseconds(): int
    {
        return match ($this) {
            self::WhileWorkRuns => self::WHILE_WORK_RUNS_MS,
        };
    }

    /**
     * How long this cadence is in seconds, which is what a sentence says.
     *
     * `intdiv` rather than a second constant, so the two numbers cannot drift:
     * a cadence changed in milliseconds changes the sentence with it, and there
     * is no arrangement in which the screen says one thing and does another.
     */
    public function seconds(): int
    {
        return intdiv($this->milliseconds(), self::A_SECOND);
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. The line it names counts on the number beside it, because the
     * sentence is the whole requirement: a cadence nobody is told about is not
     * a stated one.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.every.%s', $this->value);
    }
}
