<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;

/**
 * How often a screen looks again, and the one place each cadence is decided.
 *
 * A screen whose content can change while it is open refreshes on a cadence it
 * declares, and does not rely on the operator leaving and returning to see a
 * change. The cadence is declared here and in the screen's `#[Poll]`, where a
 * test reads it, and it is not shown: what tells an operator whether what they
 * are looking at is current is the age a reading carries once it is not.
 *
 * **The number is a constant so every screen that waits on the same thing
 * waits as long.** `#[Poll]` takes a constant expression, and a number written
 * at the attribute is one nothing else can check.
 *
 * **This is not in tension with one-read-per-frame.** That rule refuses a screen that
 * reads again to fill in its own fields — four connections to draw one frame, on a
 * home network, to a machine that may be asleep. A cadence re-reads the
 * screen's one reading where the answer changes without anybody touching the
 * phone: work the stack is carrying out, and what a stack shows that moves on
 * its own while the screen is open. Which screens those are is each screen's
 * own declaration, {@see ItsContent}.
 */
enum HowOftenAScreenLooks: string
{
    /**
     * While a stack is carrying out a repair the operator agreed to.
     *
     * Five seconds because a repair takes seconds to minutes and an operator is watching
     * the screen while it runs — long enough not to hammer a machine on a home
     * network, short enough that *it finished* arrives while they are still
     * looking.
     */
    case WhileWorkRuns = 'while_work_runs';

    /**
     * While a screen holds a subscription to what a stack says about itself.
     *
     * Taking what has arrived sends nothing to the stack, so this is how often
     * the screen looks at what it already holds, and how soon a silence past
     * the contract's bound is noticed. Two seconds because the core gathers
     * every second: a change is on the screen within two of it happening, and
     * the screen is drawn half as often as the core speaks.
     */
    case WhileListening = 'while_listening';

    /**
     * After a subscription broke or could not be opened, before it is opened again.
     *
     * Ten seconds because opening is a call to a machine that has just failed
     * to answer, and one a stack that is restarting is not ready for sooner.
     */
    case AfterABreak = 'after_a_break';

    /**
     * While a screen shows what moves second by second: traffic on the line,
     * what is leaving, what stopped coming in and one download on its way.
     *
     * Five seconds, the cadence work the operator started is watched at: what
     * these show is worth as much as the moment it was read.
     */
    case WhileItMoves = 'while_it_moves';

    /**
     * While a screen shows what changes on its own, but slowly: requests,
     * allowances, room, copies, versions and history.
     *
     * A minute, because none of it changes from one second to the next, and a
     * machine on a home network is asked once a minute for as long as the
     * screen stays open rather than twelve times.
     */
    case WhileOpen = 'while_open';

    /** The interval, in the milliseconds `#[Poll]` counts. */
    public const int WHILE_WORK_RUNS_MS = 5_000;

    /** The interval a screen holding a subscription looks at what arrived, in milliseconds. */
    public const int WHILE_LISTENING_MS = 2_000;

    /** The wait before a broken subscription is opened again, in milliseconds. */
    public const int AFTER_A_BREAK_MS = 10_000;

    /** The interval a screen showing what moves second by second looks again, in milliseconds. */
    public const int WHILE_IT_MOVES_MS = 5_000;

    /** The interval a screen showing what changes slowly looks again, in milliseconds. */
    public const int WHILE_OPEN_MS = 60_000;

    /** How long this cadence is, in the milliseconds the platform counts. */
    public function milliseconds(): int
    {
        return match ($this) {
            self::WhileWorkRuns => self::WHILE_WORK_RUNS_MS,
            self::WhileListening => self::WHILE_LISTENING_MS,
            self::AfterABreak => self::AFTER_A_BREAK_MS,
            self::WhileItMoves => self::WHILE_IT_MOVES_MS,
            self::WhileOpen => self::WHILE_OPEN_MS,
        };
    }

    /**
     * How long this cadence is in seconds, which is what a wait is measured in.
     *
     * `intdiv` rather than a second constant, so the two numbers cannot drift:
     * a cadence changed in milliseconds changes the wait with it.
     */
    public function seconds(): int
    {
        return intdiv($this->milliseconds(), DecimalPrefix::Kilo->value);
    }
}
