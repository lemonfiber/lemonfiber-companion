<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** How a back-end's deliveries went: its newest, every failure since the last confirmed one, and any still to be tried again. */
final readonly class HowItsDeliveriesWent
{
    private function __construct(
        private ?ADelivery $last,
        private FailedDeliveries $failures,
        private ?ARetry $retry,
    ) {}

    /** No delivery yet. */
    public static function nothingYet(): self
    {
        return new self(null, FailedDeliveries::these(), null);
    }

    /** The newest delivery. */
    public static function lastly(ADelivery $last): self
    {
        return new self($last, FailedDeliveries::these(), null);
    }

    /** The same, with every delivery failed since the last confirmed one, newest first. */
    public function failedSince(FailedDeliveries $failures): self
    {
        return new self($this->last, $failures, $this->retry);
    }

    /** The same, with a delivery still to be tried again. */
    public function retrying(ARetry $retry): self
    {
        return new self($this->last, $this->failures, $retry);
    }

    public function failures(): FailedDeliveries
    {
        return $this->failures;
    }

    /**
     * The newest delivery, or that there has been none.
     *
     * @template TDelivered of object
     * @template TNone of object
     *
     * @param  Closure(ADelivery): TDelivered $delivered
     * @param  Closure(): TNone               $none
     * @return TDelivered|TNone
     */
    public function last(Closure $delivered, Closure $none): object
    {
        return $this->last instanceof ADelivery ? $delivered($this->last) : $none();
    }

    /**
     * The delivery still to be tried again, or that none waits.
     *
     * @template TWaits of object
     * @template TNone of object
     *
     * @param  Closure(ARetry): TWaits $waits
     * @param  Closure(): TNone        $none
     * @return TWaits|TNone
     */
    public function retry(Closure $waits, Closure $none): object
    {
        return $this->retry instanceof ARetry ? $waits($this->retry) : $none();
    }
}
