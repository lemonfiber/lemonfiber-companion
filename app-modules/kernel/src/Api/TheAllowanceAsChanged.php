<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What one run of choosing what the household may ask for reported: whether it was a rehearsal, and what the stack said of it.
 */
final readonly class TheAllowanceAsChanged
{
    private function __construct(private WhetherItWasRehearsed $did, private Sentences $said) {}

    /** The stack's report, its sentences in the order it wrote them. */
    public static function said(WhetherItWasRehearsed $did, Sentences $said): self
    {
        return new self($did, $said);
    }

    /** Whether the run rehearsed the change or made it. */
    public function did(): WhetherItWasRehearsed
    {
        return $this->did;
    }

    /** What the stack said: what the change comes to first, then anything it noticed about the household. */
    public function whatItSays(): Sentences
    {
        return $this->said;
    }
}
