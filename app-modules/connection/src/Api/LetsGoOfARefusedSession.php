<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;

/**
 * The effect half of letting go of a refused session, written once for everything that needs it.
 *
 * Five screens resume a session and hand it to a stack, and every one of them
 * has to let go of a session that stack has just refused. The decision is the
 * same decision, the effect is the same effect, and five copies of a rule about
 * whether somebody is signed in is five chances to come to disagree — which is
 * the failure {@see Obstacle::meansWeAreSignedOut()} was written to end one
 * layer down, and it would be odd to rebuild it one layer up.
 *
 * **A trait rather than a collaborator, because there is no state here.** What
 * this needs is the screen's own store, and a type handed out to be called back
 * would mean five constructors changing to hold something that answers one
 * question. `WhereAStackIs` is the other shape and it earns its keep by holding
 * an identifier; this holds nothing.
 *
 * It is also what keeps the screens under the twenty-method ceiling.
 * Three of them arrived at twenty-one the day this effect was added to each,
 * which is what a cross-cutting concern looks like when it is pasted.
 *
 * **It reads the user's own `$storage`.** That is the coupling, stated here
 * because a trait cannot declare it: every screen that resumes a session
 * already holds the store it resumed from, and so does
 * {@see HearingEachStack}, which resumes one for each stack the lists of
 * stacks listen to. A trait that took the store as an argument would be a
 * function with extra steps.
 */
trait LetsGoOfARefusedSession
{
    /**
     * Stop holding a session the stack has just refused.
     *
     * The half of the requirement that is an effect rather than a value. A fold
     * already renders a refused credential as the signed-out state, so nothing
     * already loaded reaches a template — but a fold cannot forget anything,
     * and a session left in the store is resumed on the next frame and refused
     * again. The operator would be looking at a sign-in prompt over a device
     * that still believes it is signed in.
     *
     * Whether an obstacle means that is {@see Obstacle::meansWeAreSignedOut()}'s
     * decision, asked here rather than answered again, so a screen cannot come
     * to disagree with the fold it hands the same obstacle to.
     */
    private function letGoOfTheSession(Obstacle $why, Stack $stack): void
    {
        if (! $why->meansWeAreSignedOut()) {
            return;
        }

        $this->storage->forget($stack->id());
    }

    /**
     * The `met` arm of an answer read with a held session, letting go of the
     * session first where the refusal means it.
     *
     * What `then` answers is what the arm answered before: the fold's own
     * reading of the obstacle. Handed it rather than written around it, so the
     * effect is spelled once and every arm that needs it says so in one line.
     *
     * @template T of object
     *
     * @param Closure(Obstacle): T $then
     *
     * @return Closure(Obstacle): T
     */
    private function lettingGoIfRefused(Stack $stack, Closure $then): Closure
    {
        return function (Obstacle $why) use ($stack, $then): object {
            $this->letGoOfTheSession($why, $stack);

            return $then($why);
        };
    }
}
