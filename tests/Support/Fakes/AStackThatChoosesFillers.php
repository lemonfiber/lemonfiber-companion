<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;
use function count;

use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ChoosingAFiller;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheFill;

use function sprintf;

/**
 * A stack where a test says what each question about a choice of filler comes to, and which remembers every one.
 *
 * Answers in the order they are handed, and goes on giving the last once the
 * rest are spent, so a screen that reads a choice, agrees, and reads it again
 * is told each in turn.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatChoosesFillers implements ChoosingAFiller
{
    /** @var list<string> every question, in order, as one line each */
    private array $asked = [];

    /** @param list<WhatBecameOfTheFill> $answers */
    private function __construct(private array $answers) {}

    /** A stack that answers each question with the next of these. */
    public static function answering(WhatBecameOfTheFill $first, WhatBecameOfTheFill ...$rest): self
    {
        return new self([$first, ...array_values($rest)]);
    }

    /**
     * Every question it was asked, as one line each.
     *
     * @return list<string>
     */
    public function asked(): array
    {
        return $this->asked;
    }

    public function whatItWouldComeTo(Stack $stack, Session $session, Capability $capability, ServiceId $service): WhatBecameOfTheFill
    {
        $this->asked[] = sprintf('would %s answer %s', $service->named(), $capability->named());

        return $this->next();
    }

    public function choose(Stack $stack, Session $session, AFillAgreed $agreed): WhatBecameOfTheFill
    {
        $this->asked[] = sprintf('choose %s for %s, agreeing to %s, because "%s"', $agreed->service()->named(), $agreed->capability()->named(), $agreed->offer(), $agreed->reason());

        return $this->next();
    }

    /** The next answer, or the last once the rest are spent. */
    private function next(): WhatBecameOfTheFill
    {
        return count($this->answers) > 1 ? array_shift($this->answers) : $this->answers[0];
    }
}
