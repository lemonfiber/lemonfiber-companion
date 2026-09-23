<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something the stack found and cannot act on, and why not.
 *
 * The stack describing its own limits rather than its condition. Those are
 * different answers and a screen that mixed them would report a limitation as
 * something wrong — which is the distinction this type exists to keep, and the
 * reason it carries no severity, no state and nothing to sort by.
 *
 * **Both halves, or neither.** *There is something here lemonfiber cannot help
 * with* is not something an operator can act on, and a reason attached to
 * nothing is worse. The constructor refuses either alone, which is
 * {@see Unfilled}'s argument about its own two halves.
 *
 * What is named is *the name the thing that found it gives it* — a service id
 * where the stack runs one, a project and service where a survey found one — so
 * it is carried as a string rather than as a {@see ServiceId}. Narrowing it
 * here would refuse the survey's form of the answer, and the survey is where
 * this field says the most.
 */
final readonly class Unsupported
{
    private function __construct(
        private string $what,
        private string $because,
    ) {}

    /** Something the stack cannot act on, named as whatever found it names it, and why. */
    public static function of(string $what, string $because): self
    {
        $named = trim($what);

        if ($named === '') {
            throw ALimitSaysNothing::aboutWhat();
        }

        $reason = trim($because);

        if ($reason === '') {
            throw ALimitSaysNothing::why();
        }

        return new self($named, $reason);
    }

    /** What the stack cannot act on. */
    public function what(): string
    {
        return $this->what;
    }

    /**
     * Why it cannot, in the stack's own words.
     *
     * Carried as written rather than rephrased, for {@see TurnedDown}'s reason:
     * an operator reads the same machine through the stack's own interfaces,
     * and a second wording here would be a second vocabulary for one limit.
     */
    public function because(): string
    {
        return $this->because;
    }
}
