<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One area the operator told the stack to leave alone, as a card draws it. */
final readonly class AnAreaLeftAloneAsShown
{
    /**
     * @param string                       $area       the area, in the operator's own words
     * @param bool                         $areaCut    whether the stack cut the area short
     * @param string                       $why        the operator's reason, in their own words
     * @param bool                         $whyCut     whether the stack cut the reason short
     * @param list<AThingLeftAloneAsShown> $covers     every thing it covers that the stack listed
     * @param int                          $coversMore how many more it covers that the stack left out
     */
    public function __construct(
        public string $area,
        public bool $areaCut,
        public string $why,
        public bool $whyCut,
        public array $covers,
        public int $coversMore,
    ) {}

    /** Whether the declaration covers nothing in this stack. */
    public function coversNothing(): bool
    {
        return $this->covers === [] && $this->coversMore === 0;
    }
}
