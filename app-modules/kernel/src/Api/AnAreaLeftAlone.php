<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One area the operator told the stack to leave alone, in their own words, with what it covers now. */
final readonly class AnAreaLeftAlone
{
    use ReadsWhatIsLeftAlone;

    private function __construct(
        public string $area,
        public bool $areaCut,
        public string $why,
        public bool $whyCut,
        public WhatAnAreaCovers $covers,
    ) {}

    public static function declared(string $area, bool $areaCut, string $why, bool $whyCut, WhatAnAreaCovers $covers): self
    {
        return new self(
            self::said($area, 'area'),
            $areaCut,
            self::said($why, 'reason'),
            $whyCut,
            $covers,
        );
    }
}
