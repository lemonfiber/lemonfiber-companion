<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a repair the operator confirmed.
 *
 * `N2-R6` asks for two things and the second is the one a screen forgets: a
 * repair confirmed against one reading must not be carried out if the reading
 * has changed, and the app must **refuse and re-offer**. Refusing alone leaves
 * somebody looking at a button that did nothing.
 *
 * So the refusing arm is handed the reading as it is now — not a flag, not a
 * message. A screen that receives it has everything it needs to show the
 * operator what changed and ask again, and a screen that wanted to show a bare
 * error has to go out of its way to discard what it was given.
 */
final readonly class Carried
{
    private function __construct(private Repair $repair, private ?Reading $moved) {}

    /** The reading still held, so the repair went to the stack. */
    public static function out(Repair $repair): self
    {
        return new self($repair, null);
    }

    /**
     * The reading moved between the confirmation and the act (`N2-R6`).
     *
     * Takes the repair as well as what the reading is *now*, because "refuse"
     * and "re-offer" are one requirement rather than two. The operator
     * confirmed a repair for a situation that no longer exists; what they need
     * next is that same repair, offered against the situation that does. An arm
     * handed only the new reading would leave the screen to remember which
     * repair this was about, and a screen that remembers wrong offers the
     * wrong one.
     */
    public static function refusedBecauseTheReadingMoved(Repair $repair, Reading $now): self
    {
        return new self($repair, $now);
    }

    /**
     * @template TOut of object
     * @template TRefused of object
     *
     * @param  Closure(Repair): TOut  $out
     * @param  Closure(Repair, Reading): TRefused  $refused
     * @return TOut|TRefused
     */
    public function either(Closure $out, Closure $refused): object
    {
        // Read off the refusal, the way `Kept` does: the arm with something to
        // explain is the one the type is written around, and a fall-through is
        // how a branch becomes the one nobody tested.
        return $this->moved instanceof Reading
            ? $refused($this->repair, $this->moved)
            : $out($this->repair);
    }
}
