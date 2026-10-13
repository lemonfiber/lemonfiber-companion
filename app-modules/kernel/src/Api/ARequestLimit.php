<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How many requests a period allows, and how many days that period is.
 *
 * The two arrive together: half a limit is a figure over no period, or a period with no figure.
 */
final readonly class ARequestLimit
{
    /**
     * @param int<0, max> $requests
     * @param int<0, max> $days
     */
    private function __construct(private int $requests, private int $days) {}

    /** This many requests every this many days; a count below none is refused. */
    public static function of(int $requests, int $days): self
    {
        if ($requests < 0) {
            throw AllowanceChangeAsksNothing::below('requests', $requests);
        }

        if ($days < 0) {
            throw AllowanceChangeAsksNothing::below('days', $days);
        }

        return new self($requests, $days);
    }

    /** @return int<0, max> how many requests the period allows */
    public function requests(): int
    {
        return $this->requests;
    }

    /** @return int<0, max> how long the period is, in days */
    public function days(): int
    {
        return $this->days;
    }
}
