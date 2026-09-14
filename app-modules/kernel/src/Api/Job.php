<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The name a stack gave a piece of work it agreed to do.
 *
 * `N2-R7` is that every action on this surface arrives as a job: a stack does
 * not answer *done*, it answers *I have started, ask me about this name*. So a
 * handle is what an action produces, and the outcome is a separate reading.
 *
 * **A handle is not a pending action.** `N1-R41` refuses to retain an
 * undelivered action, to replay one on reconnecting, or to present one as
 * pending — and a job is none of those, which is the distinction that makes
 * this type safe to hold. The action was delivered: the stack acknowledged it
 * and named it. Asking after the name is a *read*, and repeating that read
 * changes nothing. Replaying an action the stack never received would be
 * inventing one; asking again about work it confirmed is the opposite.
 *
 * A type rather than a string because the name of a job and the name of an
 * agreement both arrive on the same envelope and are both strings. Passing one
 * where the other belongs compiles, ships, and asks a stack about work it
 * never started.
 */
final readonly class Job
{
    private function __construct(private string $job) {}

    public static function named(string $job): self
    {
        $trimmed = trim($job);

        if ($trimmed === '') {
            throw JobHasNoName::inAnAcknowledgement();
        }

        return new self($trimmed);
    }

    /** The name, for asking the stack after it. */
    public function shown(): string
    {
        return $this->job;
    }

    public function is(self $other): bool
    {
        return $this->job === $other->job;
    }
}
