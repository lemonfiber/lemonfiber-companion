<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * How a stack shares its line with the household, and what that costs.
 *
 * Where the line stands and what that means, and each direction's limit in the
 * one sentence the stack writes for it — the sentence carries the figure a
 * share is a share of, so that rule is kept where the stack keeps it. Beside
 * those: what is outside every limit, and what to know before trusting any of
 * it.
 *
 * **What the stack may not know is added, never defaulted.** A line nobody
 * measured has no {@see WhatTheLineCarries}; a stack with no cap has no
 * {@see AMonthlyCap}, which keeps *no cap* apart from *a cap of nothing*. Each
 * is read through a fold, so a screen says which arm it is on rather than
 * testing for a blank.
 */
final readonly class HowTheLineIsShared
{
    private function __construct(
        private WhereTheLineStands $stands,
        private string $means,
        private string $downSays,
        private string $upSays,
        private Remarks $cautions,
        private Remarks $untouched,
        private ?WhatTheLineCarries $capacity = null,
        private ?AMonthlyCap $cap = null,
        private ?Remark $spentCapDoes = null,
        private ?Remark $uploadCosts = null,
    ) {}

    /** Where the line stands, what that means, each direction's limit, and what surrounds them. */
    public static function standing(
        WhereTheLineStands $stands,
        string $means,
        string $downSays,
        string $upSays,
        Remarks $cautions,
        Remarks $untouched,
    ): self {
        return new self($stands, self::said('means', $means), self::said('down', $downSays), self::said('up', $upSays), $cautions, $untouched);
    }

    /** The same reading, with what the line was measured to carry. */
    public function measuredAt(WhatTheLineCarries $capacity): self
    {
        return new self($this->stands, $this->means, $this->downSays, $this->upSays, $this->cautions, $this->untouched, $capacity, $this->cap, $this->spentCapDoes, $this->uploadCosts);
    }

    /** The same reading, with the monthly cap that was declared. */
    public function cappedAt(AMonthlyCap $cap): self
    {
        return new self($this->stands, $this->means, $this->downSays, $this->upSays, $this->cautions, $this->untouched, $this->capacity, $cap, $this->spentCapDoes, $this->uploadCosts);
    }

    /** The same reading, saying what a spent cap is doing to the figures. */
    public function withASpentCapDoing(Remark $doing): self
    {
        return new self($this->stands, $this->means, $this->downSays, $this->upSays, $this->cautions, $this->untouched, $this->capacity, $this->cap, $doing, $this->uploadCosts);
    }

    /** The same reading, saying what throttling the upload costs. */
    public function withUploadCosting(Remark $costs): self
    {
        return new self($this->stands, $this->means, $this->downSays, $this->upSays, $this->cautions, $this->untouched, $this->capacity, $this->cap, $this->spentCapDoes, $costs);
    }

    /** Where the line stands. */
    public function stands(): WhereTheLineStands
    {
        return $this->stands;
    }

    /** What that means for the household. */
    public function means(): string
    {
        return $this->means;
    }

    /** The download limit, in the stack's one sentence. */
    public function downSays(): string
    {
        return $this->downSays;
    }

    /** The upload limit, in the stack's one sentence. */
    public function upSays(): string
    {
        return $this->upSays;
    }

    /** What is worth knowing about this reading before trusting it. */
    public function cautions(): Remarks
    {
        return $this->cautions;
    }

    /** What is outside every limit here. */
    public function untouched(): Remarks
    {
        return $this->untouched;
    }

    /**
     * What the line carries, or that nothing measured it.
     *
     * @template TMeasured of object
     * @template TUnmeasured of object
     *
     * @param Closure(WhatTheLineCarries): TMeasured $measured
     * @param Closure(): TUnmeasured                 $unmeasured
     *
     * @return TMeasured|TUnmeasured
     */
    public function capacity(Closure $measured, Closure $unmeasured): object
    {
        return $this->capacity instanceof WhatTheLineCarries ? $measured($this->capacity) : $unmeasured();
    }

    /**
     * The monthly cap, or that none was declared.
     *
     * @template TCapped of object
     * @template TUncapped of object
     *
     * @param Closure(AMonthlyCap): TCapped $capped
     * @param Closure(): TUncapped          $uncapped
     *
     * @return TCapped|TUncapped
     */
    public function cap(Closure $capped, Closure $uncapped): object
    {
        return $this->cap instanceof AMonthlyCap ? $capped($this->cap) : $uncapped();
    }

    /**
     * What a spent cap is doing to the figures, or that none is spent.
     *
     * @template TDoing of object
     * @template TNothing of object
     *
     * @param Closure(string): TDoing $doing
     * @param Closure(): TNothing     $nothing
     *
     * @return TDoing|TNothing
     */
    public function spentCap(Closure $doing, Closure $nothing): object
    {
        return $this->spentCapDoes instanceof Remark ? $doing($this->spentCapDoes->words()) : $nothing();
    }

    /**
     * What throttling the upload costs, or that no upload limit is in force.
     *
     * @template TCosts of object
     * @template TNothing of object
     *
     * @param Closure(string): TCosts $costs
     * @param Closure(): TNothing     $nothing
     *
     * @return TCosts|TNothing
     */
    public function uploadCost(Closure $costs, Closure $nothing): object
    {
        return $this->uploadCosts instanceof Remark ? $costs($this->uploadCosts->words()) : $nothing();
    }

    /** A word, refused where it is blank. */
    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw LineSaysNothing::about($field);
        }

        return $word;
    }
}
