<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** What following one item found: how sure, how far it got, what was tried, where services disagree, and how much of it is here. */
final readonly class WhatTheTraceFound
{
    private function __construct(
        private HowSureTheTraceIs $sure,
        private HowFarItGot $got,
        private TheMomentsInItsHistory $history,
        private WhereTheServicesDisagree $disagreements,
        private HowMuchOfItIsHere $here,
    ) {}

    public static function traced(
        HowSureTheTraceIs $sure,
        HowFarItGot $got,
        TheMomentsInItsHistory $history,
        WhereTheServicesDisagree $disagreements,
        HowMuchOfItIsHere $here,
    ): self {
        return new self($sure, $got, $history, $disagreements, $here);
    }

    public function sure(): HowSureTheTraceIs
    {
        return $this->sure;
    }

    public function got(): HowFarItGot
    {
        return $this->got;
    }

    public function history(): TheMomentsInItsHistory
    {
        return $this->history;
    }

    public function disagreements(): WhereTheServicesDisagree
    {
        return $this->disagreements;
    }

    public function here(): HowMuchOfItIsHere
    {
        return $this->here;
    }
}
