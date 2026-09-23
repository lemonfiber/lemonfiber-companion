<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use Closure;

use function count;

use IteratorAggregate;
use Traversable;

use function trim;

/**
 * What this machine keeps running on the stack's behalf, and what keeps it.
 *
 * A typed collection rather than an array (`D1`). The order is the stack's own,
 * which is the order it lists commands in — this app holds no opinion about
 * which of them matters more, and sorting would be inventing one.
 *
 * **What keeps them running comes first.** It is a fact about the machine
 * rather than about any row, so putting it before the variadic means a listing
 * that could be built from rows alone is unspellable — {@see Stalled::of()}'s
 * argument. Read off the rows instead, *this platform has no manager* would be
 * inferred from every row saying `unsupported`, which is the same answer until
 * one row differs and then it is a platform claim made from a single service.
 *
 * **Empty is a legitimate value**: a machine that hosts nothing still has a
 * manager, and that is a state rather than a gap. It is told apart from a stack
 * that could not be asked by the reader that builds this, not here, which is
 * `C2` applied the way {@see Stalled} applies it.
 *
 * @implements IteratorAggregate<int, Unattended>
 */
final readonly class WhatRunsUnattended implements IteratorAggregate
{
    /** @param array<int, Unattended> $commands */
    private function __construct(
        private WhatKeepsItRunning $keeper,
        private ?string $instruction,
        private array $commands,
    ) {}

    /**
     * A listing as one machine gave it, from a machine that has a manager.
     *
     * Not habit on the reindex: a variadic collected from named arguments has
     * string keys, and everything below reads this by position.
     */
    public static function keptBy(WhatKeepsItRunning $keeper, Unattended ...$commands): self
    {
        return new self($keeper, null, array_values($commands));
    }

    /**
     * A machine with no manager this product configures, and what to do instead.
     *
     * A named constructor rather than a nullable parameter on the one above,
     * which is what makes two mistakes unspellable at once: an unsupported
     * machine with no instruction — the row that renders as *off* and invites
     * switching on something nobody can switch — and an instruction attached to
     * a machine that has a manager, which would tell an operator to go and do
     * by hand what the product already did.
     *
     * The keeper is not a parameter for the same reason
     * {@see Wanted::turnedDown()} does not take a standing.
     */
    public static function unsupported(string $instruction, Unattended ...$commands): self
    {
        $said = trim($instruction);

        if ($said === '') {
            throw InstructionSaysNothing::whereThereIsNoManager();
        }

        return new self(WhatKeepsItRunning::Unsupported, $said, array_values($commands));
    }

    /**
     * What keeps these running, which is a fact about the machine.
     *
     * Published rather than folded into the rows because it is the sentence a
     * screen puts above them, and a screen cannot ask a row about it.
     */
    public function whatKeepsThem(): WhatKeepsItRunning
    {
        return $this->keeper;
    }

    /**
     * Say what to do instead, or say that the machine does this itself.
     *
     * Two arms rather than a nullable getter (`C2`). The arms are also what
     * keeps *not available here* from rendering as *off*: a screen reaching the
     * first has a sentence to draw and no control, and one reaching the second
     * has a machine that already does this.
     *
     * @template TInstead of object
     * @template TItself of object
     *
     * @param  Closure(string): TInstead  $instead
     * @param  Closure(): TItself  $itself
     * @return TInstead|TItself
     */
    public function whereItCannot(Closure $instead, Closure $itself): object
    {
        return $this->instruction === null ? $itself() : $instead($this->instruction);
    }

    /**
     * Everything installed that is not running, in the order the stack gave.
     *
     * Answered here rather than at each screen, for
     * {@see Waiting::wantsADecision()}'s reason: a screen deciding for itself
     * is how two screens come to disagree about what came back. What counts is
     * {@see HowItIsHosted::didNotComeBack()}'s, so this walks rather than
     * judges.
     */
    public function didNotComeBack(): WhatDidNotComeBack
    {
        $missing = [];

        foreach ($this->commands as $command) {
            if ($command->standing()->didNotComeBack()) {
                $missing[] = $command;
            }
        }

        return WhatDidNotComeBack::these(...$missing);
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /** @return Traversable<int, Unattended> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->commands);
    }
}
