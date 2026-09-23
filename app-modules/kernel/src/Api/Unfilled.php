<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A capability one service asks for that nothing answers.
 *
 * Two facts that only mean something together: the capability nobody fills, and
 * the service left asking for it. A list of capability names alone would say
 * that something is missing without saying who notices, and *who notices* is
 * the whole of what an operator needs in order to decide whether to care.
 *
 * **Not a fault.** A stack with an unfilled capability is not broken, and this
 * type carries no severity, no verdict and nothing to sort by. Somewhere a
 * screen may decide it is worth showing prominently; that decision is not made
 * here, because a value that arrived graded would have had it made already.
 */
final readonly class Unfilled
{
    private function __construct(
        private ServiceId $asking,
        private Capability $capability,
    ) {}

    /** One service, and the capability it asks for that nothing answers. */
    public static function of(ServiceId $asking, Capability $capability): self
    {
        return new self($asking, $capability);
    }

    /** The service left asking. */
    public function asking(): ServiceId
    {
        return $this->asking;
    }

    /** The capability nothing answers. */
    public function capability(): Capability
    {
        return $this->capability;
    }
}
