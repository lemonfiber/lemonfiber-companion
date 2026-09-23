<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One long-running command, and what stands between it and the machine.
 *
 * The stack lists every one of these, hosted or not, so that *this could come
 * back after a restart and does not* is a row an operator can read rather than
 * an absence they have to notice. A listing of only the installed ones would
 * answer the question nobody asks.
 *
 * **Three words rather than one.** `command` is how it is typed in a terminal,
 * `name` is what this product calls it, and `guarantees` is what it does for as
 * long as it runs. A row carrying only the first shows somebody
 * `lemonfiber watch --all` and asks them to decide whether it should survive a
 * reboot; a row carrying only the second shows them a label they cannot search
 * for. Both are refused blank for {@see Daemon::called()}'s reason — a row with
 * a control and no label is the worst version of this screen.
 *
 * **What is missing is carried, and only an orphan has it.** Naming what did
 * not come back is the point of the listing, and for an orphan the honest name
 * is the program the definition points at rather than the service. It is the
 * difference between *Sonarr is not running* and *the file Sonarr's service
 * definition runs is not there any more*, which are the same row and different
 * work.
 */
final readonly class Unattended
{
    private function __construct(
        private string $name,
        private string $command,
        private string $guarantees,
        private HowItIsHosted $standing,
        private ?string $missing = null,
    ) {}

    /**
     * One command as a stack listed it.
     *
     * The standing is a parameter rather than a second constructor, unlike
     * {@see Wanted::turnedDown()}: there is nothing a standing brings with it
     * that would go unspelled. What an orphan brings is the missing program,
     * and that has {@see self::orphaned()}.
     */
    public static function called(
        string $name,
        string $command,
        string $guarantees,
        HowItIsHosted $standing,
    ): self {
        return new self(
            self::said($name, $command),
            self::said($command, $name),
            self::said($guarantees, $name),
            $standing,
        );
    }

    /**
     * The same, for one installed against a program that is gone.
     *
     * A named constructor rather than a fifth parameter, which is `C2`'s cure
     * and {@see Daemon::thatExited()}'s shape: *the program it runs is missing*
     * and *nothing said anything was missing* are different facts, and a caller
     * cannot reach one while meaning the other. The standing is not a parameter
     * here, so a row naming a missing program while claiming to be running is
     * unspellable.
     */
    public static function orphaned(
        string $name,
        string $command,
        string $guarantees,
        string $missing,
    ): self {
        $was = self::called($name, $command, $guarantees, HowItIsHosted::Orphaned);

        return new self(
            $was->name,
            $was->command,
            $was->guarantees,
            $was->standing,
            self::said($missing, $name),
        );
    }

    /** What this product calls it, which is what an operator reads. */
    public function name(): string
    {
        return $this->name;
    }

    /** How it is typed in a terminal, which is what hosting installs. */
    public function command(): string
    {
        return $this->command;
    }

    /** What it does for as long as it runs, in the stack's own sentence. */
    public function guarantees(): string
    {
        return $this->guarantees;
    }

    /** What stands between it and the machine. */
    public function standing(): HowItIsHosted
    {
        return $this->standing;
    }

    /**
     * Say the program that is gone, or say that nothing is.
     *
     * Two arms rather than a nullable getter, for `C2`'s reason and
     * {@see Daemon::exit()}'s: a screen handed a null would print an empty
     * column where a path belongs, and an operator would read that as *nothing
     * is missing* on the one row where something is.
     *
     * @template TGone of object
     * @template TNothing of object
     *
     * @param  Closure(string): TGone  $gone
     * @param  Closure(): TNothing  $nothing
     * @return TGone|TNothing
     */
    public function missing(Closure $gone, Closure $nothing): object
    {
        return $this->missing === null ? $nothing() : $gone($this->missing);
    }

    /**
     * One of the three words, less the space around it.
     *
     * Shared because the three fail the same way and are owed the same refusal.
     * The other word is carried into the sentence so that a row refused for a
     * blank command can still be found — a refusal naming nothing is the defect
     * it is refusing.
     */
    private static function said(string $word, string $beside): string
    {
        $shown = trim($word);

        if ($shown === '') {
            throw UnattendedIsUnnamed::beside($beside);
        }

        return $shown;
    }
}
