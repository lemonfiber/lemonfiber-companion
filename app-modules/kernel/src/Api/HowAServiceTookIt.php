<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One service's share of an applied update: what became of it, and the way back.
 *
 * The two travel together because they are one row on a screen and one decision
 * for an operator — a service that did not start and can be rolled back is a
 * different evening from one that did not start and cannot.
 */
final readonly class HowAServiceTookIt
{
    private function __construct(
        private ServiceId $service,
        private HowItEnded $ending,
        private ?HowToUndoIt $undo = null,
    ) {}

    /**
     * What became of a service the stack named no way back for.
     *
     * Its own constructor rather than a null argument, which is `C2`'s cure and
     * {@see Daemon::thatExited()}'s shape. `N2-R19` refuses to offer undoing
     * where the stack named neither way, because an undo that is not there is
     * worse than none: it is what somebody agreed to the update on the strength
     * of. Absence has no case on {@see HowToUndoIt} and no null on this type,
     * so nothing can treat *no way back* as a kind of way back.
     */
    public static function of(ServiceId $service, HowItEnded $ending): self
    {
        return new self($service, $ending);
    }

    /** The same, where the stack named the way back. */
    public static function undoneBy(ServiceId $service, HowItEnded $ending, HowToUndoIt $undo): self
    {
        return new self($service, $ending, $undo);
    }

    public function service(): ServiceId
    {
        return $this->service;
    }

    public function ending(): HowItEnded
    {
        return $this->ending;
    }

    /**
     * Say the way back, or say there is none.
     *
     * Two arms rather than a nullable getter, for the reason
     * {@see Daemon::exit()} gives — and here the stakes are the requirement's
     * own: a screen that tested for null and drew a button anyway is exactly
     * what `N2-R19` refuses.
     *
     * @template TNamed of object
     * @template TNone of object
     *
     * @param  Closure(HowToUndoIt): TNamed  $named
     * @param  Closure(): TNone  $none
     * @return TNamed|TNone
     */
    public function undo(Closure $named, Closure $none): object
    {
        return $this->undo instanceof HowToUndoIt ? $named($this->undo) : $none();
    }
}
