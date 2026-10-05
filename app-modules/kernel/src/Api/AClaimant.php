<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One service that claims a capability, and where it came from.
 *
 * Every claimant carries its origin, whether or not the ask reaches it: a
 * contest reaches nothing, and that is where an operator most needs to know
 * which of the names in front of them is not the stack's own.
 */
final readonly class AClaimant
{
    private function __construct(
        private ServiceId $service,
        private WhoPutItThere $from,
    ) {}

    /** One claimant, and who put it there. */
    public static function of(ServiceId $service, WhoPutItThere $from): self
    {
        return new self($service, $from);
    }

    /** The service that claims it. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** Where the service came from: the stack, the operator, or a named plugin. */
    public function from(): WhoPutItThere
    {
        return $this->from;
    }
}
