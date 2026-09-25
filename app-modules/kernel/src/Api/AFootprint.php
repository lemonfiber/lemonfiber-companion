<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the stack estimates starting something would take in memory.
 *
 * An estimate the stack declares, never a measurement of what runs: the sum of
 * the estimates its services declare, with the services that declare none
 * named, so a short sum reads as short.
 */
final readonly class AFootprint
{
    private function __construct(
        private int $mebibytes,
        private Services $unestimated,
    ) {}

    /** The stack's estimate; a negative one is refused. */
    public static function estimated(int $mebibytes, Services $unestimated): self
    {
        if ($mebibytes < 0) {
            throw TheRehearsalSaysNothing::about('estimated_mib');
        }

        return new self($mebibytes, $unestimated);
    }

    /** The estimate, in mebibytes. */
    public function mebibytes(): int
    {
        return $this->mebibytes;
    }

    /** The services with no estimate of their own, which the sum leaves out. */
    public function unestimated(): Services
    {
        return $this->unestimated;
    }
}
