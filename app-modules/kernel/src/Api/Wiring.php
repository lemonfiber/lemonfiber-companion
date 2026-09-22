<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One service, and what it reaches.
 *
 * The pair the core sends: who is doing the reaching, and — through
 * {@see HowItReaches} — whether that was a capability the core resolved or a
 * name somebody gave.
 *
 * **The service doing the reaching is carried, not inferred.** A wiring read
 * without it is a statement that something reaches a download client, which is
 * not an answer to any question an operator asks. They ask why *this* service
 * is talking to *that* one, and both halves are needed to answer.
 */
final readonly class Wiring
{
    private function __construct(
        private ServiceId $by,
        private HowItReaches $reaches,
    ) {}

    /** One service, and what it reaches. */
    public static function of(ServiceId $by, HowItReaches $reaches): self
    {
        return new self($by, $reaches);
    }

    /** The service doing the reaching. */
    public function by(): ServiceId
    {
        return $this->by;
    }

    /** What it reaches, and on whose decision. */
    public function reaches(): HowItReaches
    {
        return $this->reaches;
    }
}
