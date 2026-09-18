<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One service's share of an applied update: what became of it, and the way back.
 *
 * The two travel together because they are one row on a screen and one decision
 * for an operator — a service that did not start and can be rolled back is a
 * different evening from one that did not start and can only be restored from a
 * snapshot, which brings the evening's data back with it.
 *
 * **There is no case here for *no way back*, and that is the contract's doing
 * rather than an omission.** Undoing is not offered where the stack
 * named neither way, and the wire names one on every service it reports: the
 * `applied` list's `reversal` is required and says `rollback` or `restore`. A
 * nullable here would be this side inventing a situation the stack cannot
 * describe, and a screen would carry an arm nothing can reach.
 *
 * {@see \Tests\Contract\KeepingCurrentContractTest} holds that reading to the
 * contract: the day `reversal` becomes optional, it fails and says to give this
 * type the absent case back.
 */
final readonly class HowAServiceTookIt
{
    private function __construct(
        private ServiceId $service,
        private HowItEnded $ending,
        private HowToUndoIt $undo,
    ) {}

    public static function of(ServiceId $service, HowItEnded $ending, HowToUndoIt $undo): self
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
     * The way back the stack named for this service.
     *
     * A value rather than two closure arms, because the stack names one for
     * every service and an arm for the absent case would be unreachable — which
     * is worse than a nullable, not better: it reads as a situation somebody
     * handled and is a situation nobody can produce.
     */
    public function undo(): HowToUndoIt
    {
        return $this->undo;
    }
}
