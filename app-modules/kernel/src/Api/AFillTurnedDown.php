<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A choice of what fills a capability the stack turned down, with why and in its words.
 *
 * The case is what the screen words; the stack's own sentence travels with it
 * for what only the stack can say, such as which parts of a reading moved.
 */
final readonly class AFillTurnedDown
{
    private function __construct(private WhyTheFillWasTurnedDown $why, private ARefusalInItsWords $said) {}

    public static function because(WhyTheFillWasTurnedDown $why, ARefusalInItsWords $said): self
    {
        return new self($why, $said);
    }

    /** Which refusal it was. */
    public function why(): WhyTheFillWasTurnedDown
    {
        return $this->why;
    }

    /** What the stack said. */
    public function said(): ARefusalInItsWords
    {
        return $this->said;
    }
}
