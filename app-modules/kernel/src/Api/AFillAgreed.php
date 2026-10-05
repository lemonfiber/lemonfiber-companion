<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A choice of what fills a capability, agreed to having been shown what it comes to.
 *
 * {@see ChoosingAFiller::choose()} takes one of these and nothing else, and
 * the only way to make one is from a reading the stack worked out and wrote
 * nothing for. So the choice sent is the one that was shown, and its yes is
 * the name of that reading: a reading that moved since is refused by the
 * stack. A choice already made has nothing to agree to.
 *
 * The reason is the operator's and optional. Blank is no reason, and is not
 * sent.
 */
final readonly class AFillAgreed
{
    private function __construct(
        private Capability $capability,
        private ServiceId $service,
        private string $offer,
        private string $reason,
    ) {}

    /** The choice the stack worked out, now agreed to, with the reason given; one already made is refused. */
    public static function after(AFill $shown, string $reason): self
    {
        if ($shown->wasMade()) {
            throw ThereIsNothingToAgreeTo::made();
        }

        return new self($shown->capability(), $shown->now(), $shown->agreement(), trim($reason));
    }

    /** The capability being filled. */
    public function capability(): Capability
    {
        return $this->capability;
    }

    /** The service chosen to fill it. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** The name of the reading this agrees to. */
    public function offer(): string
    {
        return $this->offer;
    }

    /** Why the operator chose it, or blank where they did not say. */
    public function reason(): string
    {
        return $this->reason;
    }
}
