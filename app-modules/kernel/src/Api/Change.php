<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One change the stack made to itself, and how far it could be put back.
 *
 * The row the record is made of. What it `did`, the `operation` that did it,
 * the `target` it was done to and when say what happened; how far it goes
 * back, how many changes came with it and where putting it back stops short
 * say what an operator could do about it now.
 *
 * **When is a {@see WhenItWasMade}**: a moment, or the fact that the stack's
 * clock would not say. It is never used to re-order the record — the stack's
 * order is kept, and a record re-sorted here would be an opinion about which of
 * two simultaneous changes came first. What it is used for is saying when, and
 * telling two changes made at one moment apart from two made in sequence.
 *
 * **How many came with it is on the row**, for the contract's own reason: an
 * operation is the unit an operator agreed to, and undoing one line of it
 * leaves a machine in a state nobody chose. The count includes this change, so
 * it is never below one.
 */
final readonly class Change
{
    private function __construct(
        private string $did,
        private string $operation,
        private string $target,
        private WhenItWasMade $when,
        private HowFarItGoesBack $reversal,
        private int $alongside,
        private ?WhereItStopsShort $short = null,
    ) {}

    /** One change as the stack recorded it. */
    public static function made(
        string $did,
        string $operation,
        string $target,
        WhenItWasMade $when,
        HowFarItGoesBack $reversal,
        int $alongside,
    ): self {
        if ($alongside < 1) {
            throw ChangeSaysNothing::alone($alongside);
        }

        return new self(
            self::said($did, 'did'),
            self::said($operation, 'operation'),
            self::said($target, 'target'),
            $when,
            $reversal,
            $alongside,
        );
    }

    /**
     * The same change, carrying where putting it back stops short.
     *
     * Takes the type rather than its sentences, so the rule that a suggestion
     * needs a reason is {@see WhereItStopsShort}'s to keep and cannot be
     * undone from here.
     */
    public function stoppingShort(WhereItStopsShort $where): self
    {
        return new self(
            $this->did,
            $this->operation,
            $this->target,
            $this->when,
            $this->reversal,
            $this->alongside,
            $where,
        );
    }

    /** What it did, in the operator's terms. */
    public function did(): string
    {
        return $this->did;
    }

    /** The operation that made it — a seed, a reconfigure, an applied fix. */
    public function operation(): string
    {
        return $this->operation;
    }

    /** What it was made to. */
    public function target(): string
    {
        return $this->target;
    }

    /** When it was made, or that the clock would not say. */
    public function when(): WhenItWasMade
    {
        return $this->when;
    }

    /** How far it could be put back. */
    public function reversal(): HowFarItGoesBack
    {
        return $this->reversal;
    }

    /** How many changes the operation that made it made, this one among them. */
    public function alongside(): int
    {
        return $this->alongside;
    }

    /**
     * Say where putting it back stops short, or say that nothing stops it.
     *
     * Two arms rather than a nullable getter, for `C2`'s reason and
     * {@see Unattended::missing()}'s: a partial reversal drawn with nothing
     * where its limits belong reads as one nobody knows the limits of.
     *
     * @template TThere of object
     * @template TNowhere of object
     *
     * @param  Closure(WhereItStopsShort): TThere $there
     * @param  Closure(): TNowhere                $nowhere
     * @return TThere|TNowhere
     */
    public function stopsShort(Closure $there, Closure $nowhere): object
    {
        return $this->short instanceof WhereItStopsShort ? $there($this->short) : $nowhere();
    }

    /**
     * One word, less the space around it, or the refusal naming which it was.
     *
     * Shared because every word here fails the same way, and the field is
     * carried so a record of forty rows refused for one blank says which kind
     * of blank to look for.
     */
    private static function said(string $word, string $field): string
    {
        $shown = trim($word);

        if ($shown === '') {
            throw ChangeSaysNothing::about($field);
        }

        return $shown;
    }
}
