<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A stack took the action on, or the reason it did not.
 *
 * What {@see Mending} answers when something is asked of a stack. `N2-R7` has
 * every action on this surface arrive as a job, so there is no arm here saying
 * *done*: the stack acknowledged the work and named it, and what became of it
 * is {@see HowTheOfferIsGoing}'s question.
 *
 * A value rather than a raise, which is `C1` and every other port here: a stack
 * asleep, one on another network and a session that has ended are ordinary
 * states of the world, and `N1-R10` says an operator is told which.
 *
 * **There is no third arm for *it refused the action itself*.** A stack that
 * answered and declined is `Obstacle::CredentialWasRefused` or a refusal
 * carried on the outcome — not a state between started and not started. Adding
 * one here would put a fourth screen between the operator and a yes they have
 * already given.
 */
final readonly class Underway
{
    private function __construct(private Job|Obstacle $answer) {}

    /** The stack took it on, and this is what to ask after it by. */
    public static function as(Job $job): self
    {
        return new self($job);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TStarted of object
     * @template TMet of object
     *
     * @param Closure(Job): TStarted $started
     * @param Closure(Obstacle): TMet $met
     *
     * @return TStarted|TMet
     */
    public function either(Closure $started, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $started($this->answer);
    }
}
