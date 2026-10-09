<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function intdiv;
use function max;

/**
 * Something a member was part-way through, how far they got, and where it plays.
 *
 * Every part is the core's answer, read as the member's own session reads it,
 * so what a member was part-way through is the media server's answer on every
 * device they play on and nothing here keeps a copy of it.
 */
final readonly class APartWay
{
    private function __construct(
        private Holding $holding,
        private HowFarIn $reached,
        private ?int $lengthInSeconds,
        private WhereItPlays $plays,
    ) {}

    /** Something that runs so many seconds, so far in. */
    public static function of(Holding $holding, HowFarIn $reached, int $lengthInSeconds, WhereItPlays $plays): self
    {
        return new self($holding, $reached, $lengthInSeconds, $plays);
    }

    /** Something whose length the server does not know, so far in. */
    public static function ofUnknownLength(Holding $holding, HowFarIn $reached, WhereItPlays $plays): self
    {
        return new self($holding, $reached, null, $plays);
    }

    /** Its id, its name, its kind and its year. */
    public function holding(): Holding
    {
        return $this->holding;
    }

    /** How far in they got. */
    public function reached(): HowFarIn
    {
        return $this->reached;
    }

    /** How long is left, in whole minutes rounded up, or unstated where the server does not know how long it runs. */
    public function left(): HowLongItRuns
    {
        if ($this->lengthInSeconds === null) {
            return HowLongItRuns::unstated();
        }

        $seconds = max(0, $this->lengthInSeconds - $this->reached->seconds());

        return HowLongItRuns::minutes(intdiv($seconds + SecondsIn::AMinute->value - 1, SecondsIn::AMinute->value));
    }

    public function plays(): WhereItPlays
    {
        return $this->plays;
    }
}
