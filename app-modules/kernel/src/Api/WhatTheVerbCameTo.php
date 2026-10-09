<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function count;
use function trim;

/**
 * What a start, a stop or a restart came to, as the stack reported it once the work finished.
 *
 * Read off the report the verb's handle is redeemed for, and never off a
 * listing taken afterwards. A listing says where the services stand now; this
 * says what the verb did, including two answers a listing cannot carry: a
 * start the stack declined to run, and a verb that was only rehearsed.
 *
 * **Whether it brought everything back is decided here, once.** It did where
 * the stack called what those services amount to `active` and named none of
 * them short of up. A report that leaves the condition out, or gives any
 * other, is not a completed start however few services it names, which is
 * what keeps a restart that brought back four services of five from reading
 * as done.
 */
final readonly class WhatTheVerbCameTo
{
    private function __construct(
        private WhetherItWasRehearsed $was,
        private WhereTheServicesEndedUp $services,
        private TheServicesLeftOut $leftOut,
        private ThePortsHeld $portsHeld,
        private TheStackEdits $edits,
        private TheCommandLine|string $ranOrWhyNot,
        private ?HowTheStackIsRunning $condition = null,
        private ?AnOffer $offer = null,
    ) {}

    /** What the stack reported of a verb it ran, with the command it ran it with. */
    public static function reported(
        WhetherItWasRehearsed $was,
        WhereTheServicesEndedUp $services,
        TheServicesLeftOut $leftOut,
        ThePortsHeld $portsHeld,
        TheStackEdits $edits,
        TheCommandLine $command,
    ): self {
        return new self($was, $services, $leftOut, $portsHeld, $edits, $command);
    }

    /** What the stack reported of a start it declined to run, with the reason it gave; a blank reason is refused. */
    public static function declined(
        WhetherItWasRehearsed $was,
        WhereTheServicesEndedUp $services,
        TheServicesLeftOut $leftOut,
        ThePortsHeld $portsHeld,
        string $why,
        TheStackEdits $edits,
    ): self {
        if (trim($why) === '') {
            throw TheHoldSaysNothing::whereAReasonWasOwed();
        }

        return new self($was, $services, $leftOut, $portsHeld, $edits, $why);
    }

    /** The same report, with what the stack says those services amount to. */
    public function amountingTo(HowTheStackIsRunning $condition): self
    {
        return new self($this->was, $this->services, $this->leftOut, $this->portsHeld, $this->edits, $this->ranOrWhyNot, $condition, $this->offer);
    }

    /** The same report, under the name the stack gave what a rehearsal of it offers, which a yes carries back. */
    public function offering(AnOffer $offer): self
    {
        return new self($this->was, $this->services, $this->leftOut, $this->portsHeld, $this->edits, $this->ranOrWhyNot, $this->condition, $offer);
    }

    /** The name the stack gave what a rehearsal of it offers, or none. */
    public function offer(): AnOffer
    {
        return $this->offer ?? AnOffer::none();
    }

    /** Whether it was a rehearsal, which changed nothing, or the verb itself. */
    public function was(): WhetherItWasRehearsed
    {
        return $this->was;
    }

    /** Every service it waited for that is not up, named with where it stood. */
    public function whatDidNotComeBack(): WhereTheServicesEndedUp
    {
        return $this->services->thatDidNotComeBack();
    }

    /** The services the plan left out, each with what it needed. */
    public function leftOut(): TheServicesLeftOut
    {
        return $this->leftOut;
    }

    /**
     * The stack files the operator edited, which it left as they set them.
     *
     * Not a problem with the run: a file somebody edited is theirs, and the
     * stack wrote around it rather than over it.
     */
    public function editsKept(): TheStackEdits
    {
        return $this->edits;
    }

    /** The ports it wanted that something else on the machine already holds. */
    public function portsHeld(): ThePortsHeld
    {
        return $this->portsHeld;
    }

    /** Whether everything it was to bring up is up, as the stack judged it. */
    public function broughtEverythingBack(): bool
    {
        return $this->condition === HowTheStackIsRunning::Active && count($this->whatDidNotComeBack()) === 0;
    }

    /**
     * What the stack says those services amount to, or that it did not say.
     *
     * @template T of object
     *
     * @param Closure(HowTheStackIsRunning): T $said
     * @param Closure(): T                     $unsaid
     *
     * @return T
     */
    public function amountsTo(Closure $said, Closure $unsaid): object
    {
        return $this->condition instanceof HowTheStackIsRunning ? $said($this->condition) : $unsaid();
    }

    /**
     * Whether the stack ran what it was asked, with the command it ran, or declined and said why.
     *
     * The command is only there for a verb that ran: a start the stack
     * declined ran nothing, so there is no command to show for it.
     *
     * @template T of object
     *
     * @param Closure(TheCommandLine): T $ran
     * @param Closure(string): T         $declined
     *
     * @return T
     */
    public function whetherItRan(Closure $ran, Closure $declined): object
    {
        return $this->ranOrWhyNot instanceof TheCommandLine ? $ran($this->ranOrWhyNot) : $declined($this->ranOrWhyNot);
    }
}
