<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a walkthrough did, the whole of it, as the stack reported it once it finished.
 *
 * The narration is carried line by line in the order it was said, and the
 * rest is what the ending is drawn from: whether what was asked for was
 * already here, where it stopped and why, what the import did with the file,
 * and where it hands the operator on to.
 *
 * **Already here is its own fact, not a kind of stop.** The stack says so in a
 * field of its own, and it is carried as one so that no screen reads it off a
 * search that matched nothing, which is a different thing that happened.
 */
final readonly class AWalkthrough
{
    private function __construct(
        private WhichWalk $shape,
        private WhereTheWalkthroughIs $state,
        private string $proves,
        private WhatWasWalked $item,
        private TheLinesItSaid $lines,
        private WhatCouldBeWalkedInstead $suggestions,
        private WhatComesNext $handover,
        private bool $inBackground,
        private bool $alreadyHere,
        private ?HowTheImportLinked $link = null,
        private ?WhereItStopped $stopped = null,
    ) {}

    /**
     * A walkthrough as the stack reported it, before anything about an import or a stop.
     *
     * What it set out to prove is required and refused blank. A handover
     * naming nothing is where it hands the operator on to nowhere, which is
     * also what a walk that did not work hands over.
     */
    public static function reported(
        WhichWalk $shape,
        WhereTheWalkthroughIs $state,
        string $proves,
        WhatWasWalked $item,
        TheLinesItSaid $lines,
        WhatCouldBeWalkedInstead $suggestions,
        WhatComesNext $handover,
        bool $inBackground,
        bool $alreadyHere,
    ): self {
        if (trim($proves) === '') {
            throw TheWalkthroughSaysNothing::about('proves');
        }

        return new self($shape, $state, $proves, $item, $lines, $suggestions, $handover, $inBackground, $alreadyHere);
    }

    /** The same walkthrough, having imported the file this way. */
    public function linked(HowTheImportLinked $link): self
    {
        return new self($this->shape, $this->state, $this->proves, $this->item, $this->lines, $this->suggestions, $this->handover, $this->inBackground, $this->alreadyHere, $link, $this->stopped);
    }

    /** The same walkthrough, having stopped here. */
    public function stoppedAt(WhereItStopped $stopped): self
    {
        return new self($this->shape, $this->state, $this->proves, $this->item, $this->lines, $this->suggestions, $this->handover, $this->inBackground, $this->alreadyHere, $this->link, $stopped);
    }

    /** Which walk this was. */
    public function shape(): WhichWalk
    {
        return $this->shape;
    }

    /** Where it ended up. */
    public function state(): WhereTheWalkthroughIs
    {
        return $this->state;
    }

    /** What it set out to prove, in the stack's words. */
    public function proves(): string
    {
        return $this->proves;
    }

    /** What it walked, or that it never got as far as choosing. */
    public function item(): WhatWasWalked
    {
        return $this->item;
    }

    /** Every line it said, in order. */
    public function lines(): TheLinesItSaid
    {
        return $this->lines;
    }

    /** What could have been walked instead, where nothing was chosen. */
    public function suggestions(): WhatCouldBeWalkedInstead
    {
        return $this->suggestions;
    }

    /** Where it hands the operator on to, in order; empty where it names nothing. */
    public function handover(): WhatComesNext
    {
        return $this->handover;
    }

    /** Whether the download was handed to the background rather than waited out. */
    public function wentOnInTheBackground(): bool
    {
        return $this->inBackground;
    }

    /** Whether what was asked for was already here, and so was not fetched again. */
    public function wasAlreadyHere(): bool
    {
        return $this->alreadyHere;
    }

    /**
     * What the import did with the file, or that it never got that far.
     *
     * @template T of object
     *
     * @param Closure(HowTheImportLinked): T $linked
     * @param Closure(): T                   $notImported
     *
     * @return T
     */
    public function link(Closure $linked, Closure $notImported): object
    {
        return $this->link instanceof HowTheImportLinked ? $linked($this->link) : $notImported();
    }

    /**
     * Where and why it stopped, or that it did not.
     *
     * @template T of object
     *
     * @param Closure(WhereItStopped): T $at
     * @param Closure(): T               $didNotStop
     *
     * @return T
     */
    public function stopped(Closure $at, Closure $didNotStop): object
    {
        return $this->stopped instanceof WhereItStopped ? $at($this->stopped) : $didNotStop();
    }
}
